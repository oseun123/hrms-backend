@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (isset($companyLogo) && $companyLogo)
            <img src="{{ $companyLogo }}" class="logo" alt="{{ $companyName ?? 'Logo' }}" style="max-height: 50px; width: auto;">
            @elseif (trim($slot) === 'Laravel')
            <img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
            @else
            {!! $companyName ?? $slot !!}
            @endif
        </a>
    </td>
</tr>