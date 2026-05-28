<?php

namespace App\Services\Payroll;

use App\Models\Payroll\TaxScheme;
use App\Models\Payroll\TaxBand;

class PayrollEngineService
{
    /**
     * Calculate annual PAYE tax based on scheme bands.
     * 
     * @param float $taxableIncome
     * @param TaxScheme $scheme
     * @return float
     */
    public function calculateAnnualTax(float $taxableIncome, TaxScheme $scheme): float
    {
        if ($taxableIncome <= 0) {
            return 0;
        }

        $totalTax = 0;
        $bands = $scheme->bands()->orderBy('lower_limit', 'asc')->get();

        foreach ($bands as $band) {
            if ($taxableIncome > $band->lower_limit) {
                $amountInBand = 0;

                if ($band->upper_limit === null || $taxableIncome <= $band->upper_limit) {
                    $amountInBand = $taxableIncome - $band->lower_limit;
                } else {
                    $amountInBand = $band->upper_limit - $band->lower_limit;
                }

                $totalTax += ($amountInBand * ($band->rate_percentage / 100)) + $band->flat_amount;
            }
        }

        return round($totalTax, 2);
    }

    /**
     * Calculate annual pension contributions.
     * 
     * @param float $baseAmount (Usually basic salary or total gross depending on policy)
     * @param float $percentage
     * @return float
     */
    public function calculatePension(float $baseAmount, float $percentage): float
    {
        return round($baseAmount * ($percentage / 100), 2);
    }

    /**
     * @param float $grossSalary
     * @param TaxScheme $scheme
     * @param array $taxDeductibleAmounts (Items like pension, housing relief etc)
     * @param float|null $pensionBase (Optional: Base for pension calculation, defaults to gross if null)
     * @param float $annualRentPaid (Optional: Actual rent paid for 2025 Act Relief)
     * @param float $otherDeductions (Optional: Total of non-pension, non-tax deductions)
     * @return array
     */
    public function calculateAnnualStructure(float $grossSalary, TaxScheme $scheme, array $taxDeductibleAmounts = [], ?float $pensionBase = null, float $annualRentPaid = 0, float $otherDeductions = 0): array
    {
        // 1. Calculate Pension (EE and ER)
        // Use provided pensionBase (BHT) or fallback to gross
        $calculationBase = $pensionBase ?? $grossSalary;

        $pensionEE = $this->calculatePension($calculationBase, $scheme->employee_pension_percentage);
        $pensionER = $this->calculatePension($calculationBase, $scheme->employer_pension_percentage);

        // 2. Calculate Total Statutory Reliefs based on the era (CRA vs 2025 Act)
        $totalRelief = $this->calculateTotalRelief($grossSalary, $scheme, $pensionEE, $taxDeductibleAmounts, $annualRentPaid);

        // 3. Calculate Taxable Income
        $taxableIncome = max(0, $grossSalary - $totalRelief);

        // 3. Calculate Tax (PAYE)
        $taxAmount = $this->calculateAnnualTax($taxableIncome, $scheme);

        // 4. Calculate Net Pay
        // Net = Gross - Pension EE - PAYE Tax - Other Reductions/Deductions
        $netPay = $grossSalary - $pensionEE - $taxAmount - $otherDeductions;

        return [
            'total_annual_gross' => $grossSalary,
            'total_annual_taxable' => $taxableIncome,
            'total_annual_tax' => $taxAmount,
            'total_annual_pension_ee' => $pensionEE,
            'total_annual_pension_er' => $pensionER,
            'total_annual_relief' => round($totalRelief, 2),
            'total_annual_net' => round($netPay, 2),
        ];
    }

    /**
     * Calculate total statutory relief based on the scheme law.
     */
    protected function calculateTotalRelief(float $grossSalary, TaxScheme $scheme, float $pensionEE, array $otherDeductibles, float $annualRentPaid = 0): float
    {
        // Base deductible items (Pension, NHF, NHIS etc)
        $standardDeductions = $pensionEE + array_sum($otherDeductibles);

        if ($scheme->apply_cra) {
            /** 
             * Finance Act 2020 (Current Law) logic:
             * "Gross Income" for CRA = Total Emoluments - Tax Exempt Items (Statutory Deductions)
             */
            $adjustedGross = max(0, $grossSalary - $standardDeductions);

            // CRA = Higher of (1% of Adjusted Gross OR 200,000) + 20% of Adjusted Gross
            $craBase = max(200000, 0.01 * $adjustedGross);
            $cra = $craBase + (0.20 * $adjustedGross);

            return $standardDeductions + $cra;
        } elseif ($scheme->apply_rent_relief) {
            /**
             * Nigeria Tax Act 2025 (2026 Law) logic:
             * CRA is abolished. 
             * A new Rent Relief is introduced: Min(MaxAmount, Percentage of Rent Paid)
             * Default (Nigeria): Min(500,000, 20% of Rent Paid)
             */
            $maxLimit = $scheme->rent_relief_max_amount ?? 500000;
            $percentage = ($scheme->rent_relief_percentage ?? 20) / 100;

            $calculatedRentRelief = min($maxLimit, $annualRentPaid * $percentage);

            return $standardDeductions + $calculatedRentRelief;
        } else {
            return $standardDeductions;
        }
    }
}
