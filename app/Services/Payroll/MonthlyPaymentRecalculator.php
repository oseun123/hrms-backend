<?php

namespace App\Services\Payroll;

use App\Models\Payroll\MonthlyPayment;
use App\Models\Payroll\SalaryComponent;
use App\Models\Payroll\MonthlyPaymentItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MonthlyPaymentRecalculator
{
    /**
     * Add an item to a monthly payment and recalculate using MARGINAL tax approach.
     * This preserves the base PAYE from the annual structure and only calculates
     * additional tax on the new component.
     */
    public function addItemAndRecalculate(
        MonthlyPayment $payment,
        SalaryComponent $component,
        float $amount,
        string $reason
    ): MonthlyPayment {

        // 1. Create the new item first
        $payment->items()->create([
            'component_id' => $component->id,
            'amount' => $amount,
            'is_one_time' => true,
            'reason' => $reason,
            'added_by' => auth('sanctum')->id(),
            'added_at' => now(),
        ]);

        // 2. Recalculate using marginal/cumulative approach
        return $this->recalculateMarginal($payment);
    }

    /**
     * Marginal recalculation: Only calculate additional tax/pension on ONE-TIME items.
     * Preserves the base calculations from the annual salary structure.
     */
    public function recalculateMarginal(MonthlyPayment $payment): MonthlyPayment
    {
        // FORCE REFRESH from DB
        $payment->refresh();
        $payment->load(['items.component', 'batchPayment.payGroup.taxScheme.bands']);

        $items = $payment->items;
        $taxScheme = $payment->batchPayment->payGroup->taxScheme;

        if (!$taxScheme) {
            throw new \Exception("Tax scheme not found for this pay group.");
        }

        // --- GET ANCHOR (Annual Template) ---
        // We use the annual structure as a baseline to ensure consistency (Jan vs Feb)
        $structure = \App\Models\Payroll\AnnualSalaryStructure::with(['items.component'])
            ->where([
                'employee_id' => $payment->employee_id,
                'status' => 'active'
            ])->first();

        // --- CALCULATE CUMULATIVE TOTALS ---
        $totalGross = 0;
        $totalTaxableEarnings = 0;
        $totalPensionableEarnings = 0;
        $totalDeductions = 0;
        $totalTaxDeductibles = 0;
        $totalHousingAmount = 0;

        // Separate recurring vs one-time earnings for correct tax calculation
        $recurringTaxableEarnings = 0;
        $oneTimeTaxableEarnings = 0;

        foreach ($items as $item) {
            $comp = $item->component;
            if (!$comp) continue;

            if ($comp->type === 'earning') {
                $totalGross += $item->amount;
                if ($comp->is_taxable) {
                    $totalTaxableEarnings += $item->amount;
                    // Separate recurring vs one-time
                    if ($item->is_one_time) {
                        $oneTimeTaxableEarnings += $item->amount;
                    } else {
                        $recurringTaxableEarnings += $item->amount;
                    }
                }
                if ($comp->is_pensionable) {
                    $totalPensionableEarnings += $item->amount;
                }
                // Track housing component
                // Track housing component
                if (($comp->code === 'HOU') || (stripos($comp->name, 'housing') !== false)) {
                    $totalHousingAmount += $item->amount;
                }
            } elseif ($comp->type === 'deduction') {
                $totalDeductions += $item->amount;
                if ($comp->is_tax_deductible) {
                    $totalTaxDeductibles += $item->amount;
                }
            }
        }

        // --- PENSION (Cumulative) ---
        $pensionPercentEE = (float) $taxScheme->employee_pension_percentage / 100;
        $pensionPercentER = (float) $taxScheme->employer_pension_percentage / 100;
        $totalPensionEE = round($totalPensionableEarnings * $pensionPercentEE, 2);
        $totalPensionER = round($totalPensionableEarnings * $pensionPercentER, 2);

        // --- RELIEF & TAX ---
        if ($structure) {
            // --- DYNAMIC ANNUAL RELIEF CALCULATION ---
            // This ensures bonuses (Case 2 & 3) increase the CRA (Consolidated Relief Allowance) correctly.

            // 0. Get Actual Annual Rent for Rent Relief (New Law)
            $actualAnnualRent = (float) ($payment->batchPayment->payGroup->annual_rent ?? 0);

            // 1. Annualize recurring components
            $annualRecurringGross = $totalGross * 12 - $oneTimeTaxableEarnings * 12;
            $annualPensionEE = $totalPensionEE * 12;
            $annualDeductibles = $totalTaxDeductibles * 12;
            $annualHousing = $totalHousingAmount * 12;

            // 2. Base Scenario (Standard Monthly Salary - Case 1)
            $recurringBaseRelief = $this->calculateRelief($recurringTaxableEarnings, $totalPensionEE + $totalTaxDeductibles, $totalHousingAmount, $taxScheme, $actualAnnualRent);
            $annualRecurringRelief = $recurringBaseRelief * 12;

            $annualRecurringTaxable = max($annualRecurringGross - $annualRecurringRelief, 0);
            $annualBaseTax = $this->calculateAnnualTax($annualRecurringTaxable, $taxScheme->bands);
            $monthlyBaseTax = round($annualBaseTax / 12, 2);

            // 3. Total Scenario (Adding One-time items - Case 2 & 3)
            $marginalTax = 0;
            if ($oneTimeTaxableEarnings > 0) {
                // Add the lump sum directly to the annual recurring gross
                $annualTotalGross = $annualRecurringGross + $oneTimeTaxableEarnings;

                // Recalculate annual relief for the total gross (to capture the extra CRA)
                $annualTotalExemptions = $annualPensionEE + $annualDeductibles;
                $annualTotalRelief = $this->calculateRelief($annualTotalGross / 12, $annualTotalExemptions / 12, $annualHousing / 12, $taxScheme, $actualAnnualRent) * 12;

                $annualTotalTaxable = max($annualTotalGross - $annualTotalRelief, 0);
                $annualTotalTax = $this->calculateAnnualTax($annualTotalTaxable, $taxScheme->bands);

                // Extra tax caused by the one-time payment
                $marginalTax = max(0, $annualTotalTax - $annualBaseTax);
            }

            $totalPAYE = $monthlyBaseTax + $marginalTax;

            \Log::info('Tax Calculation Result', [
                'employee_id' => $payment->employee_id,
                'monthly_base' => $monthlyBaseTax,
                'marginal' => $marginalTax,
                'total_paye' => $totalPAYE,
            ]);

            $totalRelief = round(($oneTimeTaxableEarnings > 0 ? ($annualTotalRelief / 12) : $recurringBaseRelief), 2);
        } else {
            /**
             * FALLBACK: If no active structure, use standard component-based relief.
             */
            $actualAnnualRent = (float) ($payment->batchPayment->payGroup->annual_rent ?? 0);
            $totalExemptions = $totalPensionEE + $totalTaxDeductibles;
            $totalRelief = $this->calculateRelief($totalTaxableEarnings, $totalExemptions, $totalHousingAmount, $taxScheme, $actualAnnualRent);
            $taxableIncome = max($totalTaxableEarnings - $totalRelief, 0);
            $totalPAYE = $this->calculateTax($taxableIncome, $taxScheme->bands);
        }

        // --- FINAL REFRESH & NET ---
        $netSalary = $totalGross - $totalPAYE - $totalPensionEE - $totalDeductions;

        // --- DATABASE UPDATE ---
        $payment->update([
            'gross_salary' => round($totalGross, 2),
            'tax_amount' => round($totalPAYE, 2),
            'pension_ee' => round($totalPensionEE, 2),
            'pension_er' => round($totalPensionER, 2),
            'total_relief' => round($totalRelief, 2),
            'net_salary' => round($netSalary, 2),
        ]);

        return $payment->fresh(['items.component']);
    }

    /**
     * Full recalculation (for removing items or complete refresh).
     * Alias for marginal recalculation.
     */
    public function recalculateFull(MonthlyPayment $payment): MonthlyPayment
    {
        return $this->recalculateMarginal($payment);
    }

    /**
     * Calculate PAYE using tax bands.
     * 
     * IMPORTANT: Tax bands are defined as ANNUAL thresholds.
     * This method accepts MONTHLY taxable income, annualizes it,
     * applies the annual tax bands, then returns the MONTHLY tax.
     */
    private function calculateTax(float $monthlyTaxableIncome, $bands): float
    {
        // Annualize the monthly income
        $annualTaxableIncome = $monthlyTaxableIncome * 12;

        $annualTax = $this->calculateAnnualTax($annualTaxableIncome, $bands);

        // Convert annual tax back to monthly
        return round($annualTax / 12, 2);
    }

    /**
     * Internal helper to calculate tax on an annual basis.
     * Takes an annual amount and returns an annual tax amount.
     */
    private function calculateAnnualTax(float $annualTaxableIncome, $bands): float
    {
        $annualTax = 0;

        // Ensure bands are ordered by lower_limit to prevent calculation errors
        $sortedBands = $bands instanceof \Illuminate\Database\Eloquent\Collection ? $bands->sortBy('lower_limit') : $bands;

        foreach ($sortedBands as $band) {
            $lower = (float) $band->lower_limit;
            $upper = $band->upper_limit ? (float) $band->upper_limit : PHP_FLOAT_MAX;
            $rate = (float) $band->rate_percentage / 100;
            $flat = (float) $band->flat_amount;

            if ($annualTaxableIncome > $lower) {
                $taxableInBand = min($annualTaxableIncome, $upper) - $lower;
                $annualTax += ($taxableInBand * $rate) + $flat;
            }
        }

        return $annualTax;
    }

    /**
     * Calculate total statutory relief.
     *
     * @param float $taxableGross Total taxable earnings
     * @param float $housingAmount Housing allowance amount (for rent relief)
     * @param $taxScheme Tax scheme configuration
     */
    private function calculateRelief(float $taxableEarnings, float $taxExemptions, float $housingAmount, $taxScheme, float $actualAnnualRent = 0): float
    {
        $relief = $taxExemptions; // Include Pension + other tax-deductibles

        // CRA Relief (Old Scheme - calculated on total taxable gross)
        if ($taxScheme->apply_cra) {
            // "Gross Income" for CRA = Total Taxable - Statutory Deductions (Exemptions)
            $adjustedGross = max(0, $taxableEarnings - $taxExemptions);

            // max(200k annual / 12, 1% of adjusted gross) + 20% of adjusted gross
            $craBase = max($adjustedGross * 0.01, 200000 / 12);
            $relief += $craBase + ($adjustedGross * 0.20);
        }

        // Rent/Housing Relief (New 2026 Scheme - calculated on ACTUAL rent paid if available, else housing allowance)
        if ($taxScheme->apply_rent_relief) {
            $percent = (float) $taxScheme->rent_relief_percentage / 100;
            $maxAmount = (float) $taxScheme->rent_relief_max_amount / 12;

            // USE ACTUAL RENT if specified on pay group, otherwise fallback to housing allowance item
            $basis = $actualAnnualRent > 0 ? ($actualAnnualRent / 12) : $housingAmount;

            $rentRelief = min($basis * $percent, $maxAmount);
            $relief += $rentRelief;
        }

        return round($relief, 2);
    }
}
