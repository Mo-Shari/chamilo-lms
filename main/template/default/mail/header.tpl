<table border="0" cellpadding="10" cellspacing="0" width="100%">
    <tr>
        <td width="245">
            <a href="{{ _p.web }}">
                {#<img src="{{ _p.web_css_theme ~ 'images/header-logo.png' }}" alt="{{ _s.site_name }}">#}
                <img
                    alt="{{ chamilo_settings_get('platform.site_name') }}"
                    src ="{{ asset('bundles/chamilocore/css/themes/'~ theme ~'/images/header-logo.png') }}"
                />
            </a>
        </td>
        <td width="100%">
            &nbsp;
        </td>
    </tr>
</table>
