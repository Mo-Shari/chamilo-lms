<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;

/**
 *  This script displays a form for registering new users.
 *  @package    chamilo.auth
 */

//quick hack to adapt the registration form result to the selected registration language
if (!empty($_POST['language'])) {
    $_GET['language'] = $_POST['language'];
}
require_once __DIR__.'/../inc/global.inc.php';
$hideHeaders = isset($_GET['hide_headers']);

$allowedFields = [
    'official_code',
    'phone',
    'status',
    'language',
    'extra_fields',
    'address'
];

$allowedFieldsConfiguration = api_get_configuration_value('allow_fields_inscription');

if ($allowedFieldsConfiguration !== false) {
    $allowedFields = isset($allowedFieldsConfiguration['fields']) ? $allowedFieldsConfiguration['fields'] : [];
    $allowedFields['extra_fields'] = isset($allowedFieldsConfiguration['extra_fields']) ? $allowedFieldsConfiguration['extra_fields'] : [];
}

$gMapsPlugin = GoogleMapsPlugin::create();
$geolocalization = $gMapsPlugin->get('enable_api') === 'true';

if ($geolocalization) {
    $gmapsApiKey = $gMapsPlugin->get('api_key');
    $htmlHeadXtra[] = '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?sensor=true&key='. $gmapsApiKey . '" ></script>';
}

$webserviceUrl = api_get_plugin_setting('logintcc', 'webservice_url');
$hash = api_get_plugin_setting('logintcc', 'hash');


$htmlHeadXtra[] = '<script>
$(document).ready(function() {
    $("#search_user").click(function() {
        
        var data = new Object();
        data.Mail = $("input[name=\'email\']").val();
        data.HashKey = "'.$hash.'";
        
        $.ajax({
            url: "'.$webserviceUrl.'/IsExistEmail",
            data: JSON.stringify(data),
            dataType: "json",
            type: "POST",
            contentType: "application/json; charset=utf-8",
            success: function (data, status) {            
                if (data.d.Exist) {
                    var monU = data.d.User;                    
                    $("input[name=\'extra_tcc_user_id\']").val(monU.UserID);
                    $("input[name=\'extra_tcc_hash_key\']").val(monU.HashKey);                                                                             
                    var $radios = $("input:radio[name=\'extra_terms_genre[extra_terms_genre]\']");
                    if (monU.Genre == "Masculin") {
                        $radios.filter(\'[value=homme]\').prop(\'checked\', true);
                    } else {                        
                        $radios.filter(\'[value=femme]\').prop(\'checked\', true);
                    }
                    $("input[name=\'lastname\']").val(monU.Nom);
                    $("input[name=\'firstname\']").val(monU.Prenom);
                    
                    var date = monU.DateNaissance; // 30/06/1986                    
                    if (date != "") {        
                        var parts = date.split(\'/\');
                        $("#extra_terms_datedenaissance").datepicker("setDate", new Date(parts[2], parts[1], parts[0]));
                    }
                
                    if (monU.Langue == "fr-FR") {                              
                        $("#language").selectpicker("val", "french");
                        $("#language").selectpicker(\'render\');                        
                    }
                    
                    if (monU.Langue == "de-DE") {                                  
                        $("#language").selectpicker("val", "german");
                        $("#language").selectpicker(\'render\');   
                    }
                    
                    $("input[name=\'extra_terms_nationalite\']").val(monU.Nationalite);
                    $("input[name=\'extra_terms_paysresidence\']").val(monU.PaysResidence);
                    $("input[name=\'extra_terms_adresse\']").val(monU.Adresse);
                    $("input[name=\'extra_terms_codepostal\']").val(monU.CP);
                    $("input[name=\'extra_terms_ville\']").val(monU.Ville);													
                } else {
                    alert("'.get_lang("UnknownUser").'");
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                alert(textStatus);
            }
        });
        
        return false;
    });
});
</script>';

$htmlHeadXtra[] = api_get_password_checker_js('#username', '#pass1');

// User is not allowed if Terms and Conditions are disabled and
// registration is disabled too.
$isNotAllowedHere = api_get_setting('allow_terms_conditions') === 'false' && api_get_setting('allow_registration') === 'false';

if ($isNotAllowedHere) {
    api_not_allowed(true, get_lang('RegistrationDisabled'));
}

if (!empty($_SESSION['user_language_choice'])) {
    $user_selected_language = $_SESSION['user_language_choice'];
} elseif (!empty($_SESSION['_user']['language'])) {
    $user_selected_language = $_SESSION['_user']['language'];
} else {
    $user_selected_language = api_get_setting('platformLanguage');
}

$form = new FormValidator('registration');

$user_already_registered_show_terms = false;
if (api_get_setting('allow_terms_conditions') == 'true') {
    $user_already_registered_show_terms = isset($_SESSION['term_and_condition']['user_id']);
    if (api_is_anonymous() === true) {
        $user_already_registered_show_terms = false;
    }
}

$sessionPremiumChecker = Session::read('SessionIsPremium');
$sessionId = Session::read('sessionId');

// Direct Link Session Subscription feature #12220
$sessionRedirect = isset($_REQUEST['s']) && !empty($_REQUEST['s']) ? $_REQUEST['s'] : null;
$onlyOneCourseSessionRedirect = isset($_REQUEST['cr']) && !empty($_REQUEST['cr']) ? $_REQUEST['cr'] : null;

if (api_get_configuration_value('allow_redirect_to_session_after_inscription_about')) {
    if (!empty($sessionRedirect)) {
        Session::write('session_redirect', $sessionRedirect);
        Session::write('only_one_course_session_redirect', $onlyOneCourseSessionRedirect);
    }
}

// Direct Link Subscription feature #5299
$course_code_redirect = isset($_REQUEST['c']) && !empty($_REQUEST['c']) ? $_REQUEST['c'] : null;
$exercise_redirect = isset($_REQUEST['e']) && !empty($_REQUEST['e']) ? $_REQUEST['e'] : null;

if (!empty($course_code_redirect)) {
    Session::write('course_redirect', $course_code_redirect);
    Session::write('exercise_redirect', $exercise_redirect);
}

if ($user_already_registered_show_terms === false) {
    // EMAIL
    $form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
    if (api_get_setting('registration', 'email') === 'true') {
        $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
    }

    $form->addButtonSearch(get_lang('SearchTCC'), 'search', ['id' => 'search_user']);

    if (api_is_western_name_order()) {
        // FIRST NAME and LAST NAME
        $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
        $form->addElement('text', 'lastname', get_lang('LastName'), array('size' => 40));
    } else {
        // LAST NAME and FIRST NAME
        $form->addElement('text', 'lastname', get_lang('LastName'), array('size' => 40));
        $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
    }
    $form->applyFilter(array('lastname', 'firstname'), 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

    if (api_get_setting('login_is_email') === 'true') {
        $form->applyFilter('email', 'trim');
        if (api_get_setting('registration', 'email') != 'true') {
            $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
        }
        $form->addRule('email', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
        $form->addRule('email', get_lang('UserTaken'), 'username_available');
    }

    $form->addRule('email', get_lang('EmailWrong'), 'email');
    if (api_get_setting('openid_authentication') === 'true') {
        $form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => 40));
    }

    // OFFICIAL CODE
    if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
        if (in_array('official_code', $allowedFields)) {
            $form->addElement(
                'text',
                'official_code',
                get_lang('OfficialCode'),
                array('size' => 40)
            );
            if (api_get_setting('registration', 'officialcode') == 'true') {
                $form->addRule(
                    'official_code',
                    get_lang('ThisFieldIsRequired'),
                    'required'
                );
            }
        }
    }

    // USERNAME
    if (api_get_setting('login_is_email') != 'true') {
        $form->addText(
            'username',
            get_lang('UserName'),
            true,
            array(
                'id' => 'username',
                'size' => USERNAME_MAX_LENGTH,
                'autocomplete' => 'off'
            )
        );
        $form->applyFilter('username', 'trim');
        $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
        $form->addRule('username', get_lang('UsernameWrong'), 'username');
        $form->addRule('username', get_lang('UserTaken'), 'username_available');
    }

    $passDiv = '<div id="password_progress"></div><div id="password-verdict"></div><div id="password-errors"></div>';

    $checkPass = api_get_setting('allow_strength_pass_checker');
    if ($checkPass === 'true') {
        $checkPass = '';
    }

    // PASSWORD
    $form->addElement(
        'password',
        'pass1',
        [get_lang('Pass'), $passDiv],
        array('id' => 'pass1', 'size' => 20, 'autocomplete' => 'off')
    );

    $checkPass = api_get_setting('allow_strength_pass_checker');
//    if ($checkPass === 'true') {
//        $form->addLabel(null,
//        '<div id="password_progress"></div><div id="password-verdict"></div><div id="password-errors"></div>'
//        );
//    }

    $form->addElement(
        'password',
        'pass2',
        get_lang('Confirmation'),
        array('id' => 'pass2', 'size' => 20, 'autocomplete' => 'off')
    );
    $form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule(array('pass1', 'pass2'), get_lang('PassTwo'), 'compare');

    if (true) {
        $form->addRule(
            'pass1',
            get_lang('PassTooEasy') . ': ' . api_generate_password(),
            'callback',
            'api_check_password'
        );
    }

    // PHONE
    if (in_array('phone', $allowedFields)) {
        $form->addElement(
            'text',
            'phone',
            get_lang('Phone'),
            array('size' => 20)
        );
        if (api_get_setting('registration', 'phone') == 'true') {
            $form->addRule(
                'phone',
                get_lang('ThisFieldIsRequired'),
                'required'
            );
        }
    }

    // Language
    if (in_array('language', $allowedFields)) {
        if (api_get_setting('registration', 'language') == 'true') {
            $form->addSelectLanguage(
                'language',
                get_lang('Language'),
                [],
                ['id' => 'language']
            );
        }
    }

    // STUDENT/TEACHER
    if (api_get_setting('allow_registration_as_teacher') != 'false') {
        if (in_array('status', $allowedFields)) {
            $form->addElement(
                'radio',
                'status',
                get_lang('Profile'),
                get_lang('RegStudent'),
                STUDENT
            );
            $form->addElement(
                'radio',
                'status',
                null,
                get_lang('RegAdmin'),
                COURSEMANAGER
            );
        }
    }

    $captcha = api_get_setting('allow_captcha');
    $allowCaptcha = $captcha === 'true';

    if ($allowCaptcha) {
        $ajax = api_get_path(WEB_AJAX_PATH).'form.ajax.php?a=get_captcha';
        $options = array(
            'width' => 220,
            'height' => 90,
            'callback' => $ajax.'&var='.basename(__FILE__, '.php'),
            'sessionVar' => basename(__FILE__, '.php'),
            'imageOptions' => array(
                'font_size' => 20,
                'font_path' => api_get_path(SYS_FONTS_PATH).'opensans/',
                'font_file' => 'OpenSans-Regular.ttf',
                //'output' => 'gif'
            )
        );

        $captcha_question =  $form->addElement('CAPTCHA_Image', 'captcha_question', '', $options);
        $form->addElement('static', null, null, get_lang('ClickOnTheImageForANewOne'));

        $form->addElement('text', 'captcha', get_lang('EnterTheLettersYouSee'), array('size' => 40));
        $form->addRule('captcha', get_lang('EnterTheCharactersYouReadInTheImage'), 'required', null, 'client');

        $form->addRule('captcha', get_lang('TheTextYouEnteredDoesNotMatchThePicture'), 'CAPTCHA', $captcha_question);
    }

    // EXTENDED FIELDS
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true'
    ) {
        $form->addHtmlEditor(
            'competences',
            get_lang('MyCompetences'),
            false,
            false,
            array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130')
        );
    }
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true'
    ) {
        $form->addHtmlEditor(
            'diplomas',
            get_lang('MyDiplomas'),
            false,
            false,
            array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130')
        );
    }
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'myteach') == 'true'
    ) {
        $form->addHtmlEditor(
            'teach',
            get_lang('MyTeach'),
            false,
            false,
            array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130')
        );
    }
    if (api_get_setting('extended_profile') == 'true' &&
        api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true'
    ) {
        $form->addHtmlEditor(
            'openarea',
            get_lang('MyPersonalOpenArea'),
            false,
            false,
            array('ToolbarSet' => 'register', 'Width' => '100%', 'Height' => '130')
        );
    }
    if (api_get_setting('extended_profile') === 'true') {
        if (api_get_setting('extendedprofile_registration', 'mycomptetences') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'mycomptetences') === 'true'
        ) {
            $form->addRule('competences', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registration', 'mydiplomas') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'mydiplomas') === 'true'
        ) {
            $form->addRule('diplomas', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registration', 'myteach') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'myteach') === 'true'
        ) {
            $form->addRule('teach', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (api_get_setting('extendedprofile_registration', 'mypersonalopenarea') === 'true' &&
            api_get_setting('extendedprofile_registrationrequired', 'mypersonalopenarea') === 'true'
        ) {
            $form->addRule('openarea', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    $form->addElement(
        'hidden',
        'extra_tcc_user_id'
    );

    $form->addElement(
        'hidden',
        'extra_tcc_hash_key'
    );

    // EXTRA FIELDS
    if (array_key_exists('extra_fields', $allowedFields) ||
        in_array('extra_fields', $allowedFields)
    ) {
        $extraField = new ExtraField('user');
        $extraFieldList = isset($allowedFields['extra_fields']) && is_array($allowedFields['extra_fields']) ? $allowedFields['extra_fields'] : [];
        $returnParams = $extraField->addElements($form, 0, [], false, false, $extraFieldList);
    }
}
if (isset($_SESSION['user_language_choice']) && $_SESSION['user_language_choice'] != '') {
    $defaults['language'] = $_SESSION['user_language_choice'];
} else {
    $defaults['language'] = api_get_setting('platformLanguage');
}
if (!empty($_GET['username'])) {
    $defaults['username'] = Security::remove_XSS($_GET['username']);
}
if (!empty($_GET['email'])) {
    $defaults['email'] = Security::remove_XSS($_GET['email']);
}

if (!empty($_GET['phone'])) {
    $defaults['phone'] = Security::remove_XSS($_GET['phone']);
}

if (api_get_setting('openid_authentication') === 'true' && !empty($_GET['openid'])) {
    $defaults['openid'] = Security::remove_XSS($_GET['openid']);
}

$defaults['status'] = STUDENT;
$defaults['extra_mail_notify_invitation'] = 1;
$defaults['extra_mail_notify_message'] = 1;
$defaults['extra_mail_notify_group_message'] = 1;

$form->setDefaults($defaults);
$content = null;

if (!CustomPages::enabled()) {
    // Load terms & conditions from the current lang
    if (api_get_setting('allow_terms_conditions') === 'true') {
        $get = array_keys($_GET);
        if (isset($get)) {
            if (isset($get[0]) && $get[0] == 'legal') {
                $language = api_get_interface_language();
                $language = api_get_language_id($language);
                $term_preview = LegalManager::get_last_condition($language);
                if (!$term_preview) {
                    //look for the default language
                    $language = api_get_setting('platformLanguage');
                    $language = api_get_language_id($language);
                    $term_preview = LegalManager::get_last_condition($language);
                }
                $tool_name = get_lang('TermsAndConditions');
                Display::display_header($tool_name);

                if (!empty($term_preview['content'])) {
                    echo $term_preview['content'];
                } else {
                    echo get_lang('ComingSoon');
                }
                Display::display_footer();
                exit;
            }
        }
    }

    $tool_name = get_lang('Registration', null, (!empty($_POST['language']) ? $_POST['language'] : $_user['language']));

    if (api_get_setting('allow_terms_conditions') === 'true' && $user_already_registered_show_terms) {
        $tool_name = get_lang('TermsAndConditions');
    }

    $home = api_get_path(SYS_APP_PATH).'home/';
    if (api_is_multiple_url_enabled()) {
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $url_info = api_get_access_url($access_url_id);
            $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
            $clean_url = api_replace_dangerous_char($url);
            $clean_url = str_replace('/', '-', $clean_url);
            $clean_url .= '/';
            $home_old  = api_get_path(SYS_APP_PATH).'home/';
            $home = api_get_path(SYS_APP_PATH).'home/'.$clean_url;
        }
    }

    if (file_exists($home.'register_top_'.$user_selected_language.'.html')) {
        $home_top_temp = @(string) file_get_contents($home.'register_top_'.$user_selected_language.'.html');
        $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
        $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
        if (!empty($open)) {
            $content = '<div class="well_border">'.$open.'</div>';
        }
    }

    // Forbidden to self-register
    if ($isNotAllowedHere) {
        api_not_allowed(true, get_lang('RegistrationDisabled'));
    }

    if (api_get_setting('allow_registration') === 'approval') {
        $content .= Display::return_message(get_lang('YourAccountHasToBeApproved'));
    }

    //if openid was not found
    if (!empty($_GET['openid_msg']) && $_GET['openid_msg'] == 'idnotfound') {
        $content .= Display::return_message(get_lang('OpenIDCouldNotBeFoundPleaseRegister'));
    }
}

$blockButton = false;
$termActivated = false;

// Terms and conditions
if (api_get_setting('allow_terms_conditions') == 'true') {
    if (!api_is_platform_admin()) {
        if (api_get_setting('show_terms_if_profile_completed') === 'true') {
            $userInfo = api_get_user_info(
                api_get_user_id(),
                false,
                false,
                true
            );
            if ($userInfo && $userInfo['status'] != ANONYMOUS) {
                $extraFieldValue = new ExtraFieldValue('user');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    api_get_user_id(),
                    'termactivated'
                );
                if (isset($value['value'])) {
                    $termActivated = !empty($value['value']) && $value['value'] == 1;
                }

                /*$extraFieldValue = new ExtraFieldValue('user');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(api_get_user_id(), 'legal_accept');
                $legalAccept = false;
                if (isset($value['value'])) {
                    list($legalId, $legalLanguageId, $legalTime) = explode(
                        ':',
                        $value['value']
                    );
                    if ($legalId) {
                        $legalAccept = true;
                    }
                }*/

                if ($termActivated === false) {
                    $blockButton = true;
                    Display::addFlash(
                        Display::return_message(
                            get_lang('TermActivatedIsNeededDescription'),
                            'warning',
                            false
                        )
                    );
                }

                if ($blockButton === false) {
                    if ((int)$userInfo['profile_completed'] !== 1) {
                        $blockButton = true;
                        Display::addFlash(
                            Display::return_message(
                                get_lang('TermYourProfileIsNotCompleted'),
                                'warning',
                                false
                            )
                        );
                    }
                }
            }
        }
    }

    // Ofaj
    if (!api_is_anonymous()) {
        $language = api_get_interface_language();
        $language = api_get_language_id($language);
        $term_preview = LegalManager::get_last_condition($language);

        if (!$term_preview) {
            //we load from the platform
            $language = api_get_setting('platformLanguage');
            $language = api_get_language_id($language);
            $term_preview = LegalManager::get_last_condition($language);

            //if is false we load from english
            if (!$term_preview) {
                $language = api_get_language_id('english'); //this must work
                $term_preview = LegalManager::get_last_condition($language);
            }
        }

        // ofaj
        if ($termActivated !== false) {
            // Version and language
            $form->addElement(
                'hidden',
                'legal_accept_type',
                $term_preview['version'].':'.$term_preview['language_id']
            );
            $form->addElement(
                'hidden',
                'legal_info',
                $term_preview['id'].':'.$term_preview['language_id']
            );
            if ($term_preview['type'] == 1) {
                $form->addElement(
                    'checkbox',
                    'legal_accept',
                    null,
                    get_lang('IHaveReadAndAgree').'&nbsp;<a href="inscription.php?legal" target="_blank">'.
                    get_lang('TermsAndConditions').'</a>'
                );
                $form->addRule(
                    'legal_accept',
                    get_lang('ThisFieldIsRequired'),
                    'required'
                );
            } else {
                $preview = LegalManager::show_last_condition($term_preview);
                $form->addElement('label', null, $preview);
            }
        }
    }
}

if ($user_already_registered_show_terms === false) {
    $form->addCheckBox(
        'extra_platformuseconditions',
        null,
        get_lang('PlatformUseConditions')
    );
    $form->addRule(
        'extra_platformuseconditions',
        get_lang('ThisFieldIsRequired'),
        'required'
    );
}

if ($blockButton) {
    if ($termActivated !== false) {
        $form->addButton(
            'submit',
            get_lang('RegisterUserOk'),
            'check',
            'primary',
            null,
            null,
            ['disabled' => 'disabled'],
            false
        );
    }
} else {
    $allow = api_get_configuration_value('allow_double_validation_in_registration');

    if ($allow && $termActivated == false) {
        $htmlHeadXtra[] = '<script>
            $(document).ready(function() {
                $("#pre_validation").click(function() {
                    $(this).hide();
                    $("#final_button").show();
                });
            });
        </script>';

        $form->addLabel(
            null,
            Display::url(
                get_lang('Ok'),
                'javascript:void',
                ['class' => 'btn btn-default', 'id' => 'pre_validation']
            )
        );
        $form->addHtml('<div id="final_button" style="display: none">');
        $form->addLabel(
            null,
            Display::return_message(get_lang('DoubleValidationMessage'), 'info')
        );
        $form->addButtonCreate(get_lang('RegisterUser'));
        $form->addHtml('</div>');
    } else {
        $form->addButtonCreate(get_lang('RegisterUser'));
    }
}

$course_code_redirect = Session::read('course_redirect');
$sessionToRedirect = Session::read('session_redirect');

if ($form->validate()) {
    $values = $form->getSubmitValues(1);
    // Make *sure* the login isn't too long
    if (isset($values['username'])) {
        $values['username'] = api_substr($values['username'], 0, USERNAME_MAX_LENGTH);
    }

    if (api_get_setting('allow_registration_as_teacher') === 'false') {
        $values['status'] = STUDENT;
    }

    if (empty($values['official_code']) && !empty($values['username'])) {
        $values['official_code'] = api_strtoupper($values['username']);
    }

    if (api_get_setting('login_is_email') == 'true') {
        $values['username'] = $values['email'];
    }

    if ($user_already_registered_show_terms &&
        api_get_setting('allow_terms_conditions') === 'true'
    ) {
        $user_id = $_SESSION['term_and_condition']['user_id'];
        $is_admin = UserManager::is_admin($user_id);
        Session::write('is_platformAdmin', $is_admin);
    } else {
        // Moved here to include extra fields when creating a user. Formerly placed after user creation
        // Register extra fields
        $extras = array();
        foreach ($values as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') {
                //an extra field
                $extras[substr($key, 6)] = $value;
            } elseif (strpos($key, 'remove_extra_') !== false) {
                $extra_value = Security::filter_filename(urldecode(key($value)));
                // To remove from user_field_value and folder
                UserManager::update_extra_field_value(
                    $user_id,
                    substr($key, 13),
                    $extra_value
                );
            }
        }

        $userFromDb = UserManager::getManager()->findUserByEmail($values['email']);
        if ($userFromDb) {
            Display::addFlash(Display::return_message(get_lang('EmailUsedTwice'), 'warning'));
            header('Location: '.api_get_self());
            exit;
        }

        $status = isset($values['status']) ? $values['status'] : STUDENT;
        $phone = isset($values['phone']) ? $values['phone'] : null;
        $values['language'] = isset($values['language']) ? $values['language'] : api_get_interface_language();
        $values['address'] = isset($values['address']) ? $values['address'] : '';

        // Creates a new user
        $user_id = UserManager::create_user(
            $values['firstname'],
            $values['lastname'],
            $status,
            $values['email'],
            $values['username'],
            $values['pass1'],
            $values['official_code'],
            $values['language'],
            $phone,
            null,
            PLATFORM_AUTH_SOURCE,
            null,
            1,
            0,
            $extras,
            null,
            true,
            false,
            $values['address'],
            true,
            $form
        );

        //update the extra fields
        $count_extra_field = count($extras);
        if ($count_extra_field > 0 && is_integer($user_id)) {
            foreach ($extras as $key => $value) {
                // For array $value -> if exists key 'tmp_name' then must not be empty
                // This avoid delete from user field value table when doesn't upload a file
                if (is_array($value)) {
                    if (array_key_exists('tmp_name', $value) && empty($value['tmp_name'])) {
                        //Nothing to do
                    } else {
                        if (array_key_exists('tmp_name', $value)) {
                            $value['tmp_name'] = Security::filter_filename($value['tmp_name']);
                        }
                        if (array_key_exists('name', $value)) {
                            $value['name'] = Security::filter_filename($value['name']);
                        }
                        UserManager::update_extra_field_value($user_id, $key, $value);
                    }
                } else {
                    UserManager::update_extra_field_value($user_id, $key, $value);
                }
            }
        }

        if ($user_id) {
            // Storing the extended profile
            $store_extended = false;

            $sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)." SET ";

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'mycomptetences') == 'true'
            ) {
                $sql_set[] = "competences = '".Database::escape_string($values['competences'])."'";
                $store_extended = true;
            }

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'mydiplomas') == 'true'
            ) {
                $sql_set[] = "diplomas = '".Database::escape_string($values['diplomas'])."'";
                $store_extended = true;
            }

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'myteach') == 'true'
            ) {
                $sql_set[] = "teach = '".Database::escape_string($values['teach'])."'";
                $store_extended = true;
            }

            if (api_get_setting('extended_profile') == 'true' &&
                api_get_setting('extendedprofile_registration', 'mypersonalopenarea') == 'true'
            ) {
                $sql_set[] = "openarea = '".Database::escape_string($values['openarea'])."'";
                $store_extended = true;
            }

            if ($store_extended) {
                $sql .= implode(',', $sql_set);
                $sql .= " WHERE user_id = ".intval($user_id)."";
                Database::query($sql);
            }

            // Saving user to Session if it was set
            if (!empty($sessionToRedirect) && !$sessionPremiumChecker) {
                $sessionInfo = api_get_session_info($sessionToRedirect);
                if (!empty($sessionInfo)) {
                    SessionManager::subscribe_users_to_session(
                        $sessionToRedirect,
                        [$user_id],
                        SESSION_VISIBLE_READ_ONLY,
                        false
                    );
                }
            }

            // Saving user to course if it was set.
            if (!empty($course_code_redirect)) {
                $course_info = api_get_course_info($course_code_redirect);
                if (!empty($course_info)) {
                    if (in_array(
                        $course_info['visibility'],
                        array(
                            COURSE_VISIBILITY_OPEN_PLATFORM,
                            COURSE_VISIBILITY_OPEN_WORLD
                        )
                    )
                    ) {
                        CourseManager::subscribe_user(
                            $user_id,
                            $course_info['code']
                        );
                    }
                }
            }

            /* If the account has to be approved then we set the account to inactive,
            sent a mail to the platform admin and exit the page.*/
            if (api_get_setting('allow_registration') === 'approval') {
                $TABLE_USER = Database::get_main_table(TABLE_MAIN_USER);
                // 1. set account inactive
                $sql = "UPDATE $TABLE_USER SET active='0' WHERE user_id = ".$user_id;
                Database::query($sql);

                // 2. Send mail to all platform admin
                $emailsubject = get_lang('ApprovalForNewAccount', null, $values['language']).': '.$values['username'];
                $emailbody = get_lang('ApprovalForNewAccount', null, $values['language'])."\n";
                $emailbody .= get_lang('UserName', null, $values['language']).': '.$values['username']."\n";

                if (api_is_western_name_order()) {
                    $emailbody .= get_lang('FirstName', null, $values['language']).': '.$values['firstname']."\n";
                    $emailbody .= get_lang('LastName', null, $values['language']).': '.$values['lastname']."\n";
                } else {
                    $emailbody .= get_lang('LastName', null, $values['language']).': '.$values['lastname']."\n";
                    $emailbody .= get_lang('FirstName', null, $values['language']).': '.$values['firstname']."\n";
                }
                $emailbody .= get_lang('Email', null, $values['language']).': '.$values['email']."\n";
                $emailbody .= get_lang('Status', null, $values['language']).': '.$values['status']."\n\n";

                $url_edit = Display::url(
                    api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id,
                    api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id
                );

                $emailbody .= get_lang('ManageUser', null, $values['language']).": $url_edit";

                $admins = UserManager::get_all_administrators();
                foreach ($admins as $admin_info) {
                    MessageManager::send_message(
                        $admin_info['user_id'],
                        $emailsubject,
                        $emailbody,
                        [],
                        [],
                        null,
                        null,
                        null,
                        null,
                        $user_id
                    );
                }

                // 3. exit the page
                unset($user_id);

                Display::display_header($tool_name);
                echo Display::page_header($tool_name);
                echo $content;
                Display::display_footer();
                exit;
            }
        }
    }

    // Terms & Conditions
    if (api_get_setting('allow_terms_conditions') === 'true') {
        // Update the terms & conditions.
        if (isset($values['legal_accept_type'])) {
            $cond_array = explode(':', $values['legal_accept_type']);
            if (!empty($cond_array[0]) && !empty($cond_array[1])) {
                $time = time();
                $condition_to_save = intval($cond_array[0]).':'.intval($cond_array[1]).':'.$time;
                UserManager::update_extra_field_value(
                    $user_id,
                    'legal_accept',
                    $condition_to_save
                );

                $bossList = UserManager::getStudentBossList($user_id);
                if ($bossList) {
                    $bossList = array_column($bossList, 'boss_id');
                    $currentUserInfo = api_get_user_info($user_id);
                    $followUpPath = api_get_path(WEB_CODE_PATH) . 'admin/user_information.php?user_id='.$currentUserInfo['id'];
                    foreach ($bossList as $bossId) {
                        $subjectEmail = sprintf(get_lang('UserXSignedTheAgreementCheckAtPageX'), $currentUserInfo['complete_name'], $followUpPath);
                        $contentEmail = sprintf(
                            get_lang('UserXSignedTheAgreementTheY'),
                            $currentUserInfo['complete_name'],
                            api_get_local_time($time)
                        );

                        MessageManager::send_message_simple(
                            $bossId,
                            $subjectEmail,
                            $contentEmail
                        );
                    }
                }
            }
        }
        $values = api_get_user_info($user_id);
    }

    /* SESSION REGISTERING */
    /* @todo move this in a function */
    $_user['firstName'] = stripslashes($values['firstname']);
    $_user['lastName'] = stripslashes($values['lastname']);
    $_user['mail'] = $values['email'];
    $_user['language'] = $values['language'];
    $_user['user_id'] = $user_id;

    $userInfo = api_get_user_info($user_id);
    $_user['status'] = $userInfo['status'];
    $is_allowedCreateCourse = isset($values['status']) && $values['status'] == 1;
    $usersCanCreateCourse = api_is_allowed_to_create_course();

    Session::write('_user', $_user);
    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);

    // Stats
    Event::eventLogin($user_id);

    // last user login date is now
    $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970
    Session::write('user_last_login_datetime', $user_last_login_datetime);
    $text_after_registration =
        '<p>'.
        get_lang('Dear', null, $_user['language']).' '.
        stripslashes(Security::remove_XSS($values['firstname'])).',<br /><br />'.
        get_lang('PersonalSettings',null,$_user['language'])."</p>";

    $form_data = array(
        'button' => Display::button('next', get_lang('Next', null, $_user['language']), array('class' => 'btn btn-primary btn-large')),
        'message' => null,
        'action' => api_get_path(WEB_PATH).'user_portal.php'
    );

    if (api_get_setting('allow_terms_conditions') === 'true' && $user_already_registered_show_terms) {
        if (api_get_setting('load_term_conditions_section') === 'login') {
            $form_data['action'] = api_get_path(WEB_PATH).'user_portal.php';
        } else {
            $courseInfo = api_get_course_info();
            if (!empty($courseInfo)) {
                $form_data['action'] = $courseInfo['course_public_url'].'?id_session='.api_get_session_id();
                $cidReset = true;
                Session::erase('_course');
                Session::erase('_cid');
            } else {
                $form_data['action'] = api_get_path(WEB_PATH).'user_portal.php';
            }
        }
    } else {
        if (!empty($values['email'])) {
            $text_after_registration.= '<p>'.get_lang('MailHasBeenSent', null, $_user['language']).'</p>';
            $diagnosticPath = '<a href="'.api_get_path(WEB_PATH).'search.php">'.api_get_path(WEB_PATH).'search.php</a>';
            $text_after_registration.= '<p>'.sprintf(get_lang('WelcomePleaseGoToDiagnosticAtX', null, $_user['language']), $diagnosticPath).'</p>';
        }

        if ($is_allowedCreateCourse) {
            if ($usersCanCreateCourse) {
                $form_data['message'] = '<p>'. get_lang('NowGoCreateYourCourse', null, $_user['language']). "</p>";
            }
            $form_data['action']  = api_get_path(WEB_CODE_PATH).'create_course/add_course.php';

            if (api_get_setting('course_validation') === 'true') {
                $form_data['button'] = Display::button(
                    'next',
                    get_lang('CreateCourseRequest', null, $_user['language']),
                    array('class' => 'btn btn-primary btn-large')
                );
            } else {
                $form_data['button'] = Display::button(
                    'next',
                    get_lang('CourseCreate', null, $_user['language']),
                    array('class' => 'btn btn-primary btn-large')
                );
                $form_data['go_button'] = '&nbsp;&nbsp;<a href="'.api_get_path(WEB_PATH).'index.php'.'">'.
                    Display::span(
                        get_lang('Next', null, $_user['language']),
                        array('class' => 'btn btn-primary btn-large')
                    ).'</a>';
            }
        } else {
            if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                $form_data['action'] = 'courses.php?action=subscribe';
                $form_data['message'] = '<p>'. get_lang('NowGoChooseYourCourses', null, $_user['language']). ".</p>";
            } else {
                $form_data['action'] = api_get_path(WEB_PATH).'user_portal.php';
            }
            $form_data['button'] = Display::button(
                'next',
                get_lang('Next', null, $_user['language']),
                array('class' => 'btn btn-primary btn-large')
            );
        }
    }

    if ($sessionPremiumChecker && $sessionId) {
        header('Location:'.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process.php?i='.$sessionId.'&t=2');
        Session::erase('SessionIsPremium');
        Session::erase('sessionId');
        exit;
    }

    SessionManager::redirectToSession();

    $redirectBuyCourse = Session::read('buy_course_redirect');
    if (!empty($redirectBuyCourse)) {
        $form_data['action'] = api_get_path(WEB_PATH).$redirectBuyCourse;
        Session::erase('buy_course_redirect');
    }

    $form_data = CourseManager::redirectToCourse($form_data);

    $form_register = new FormValidator('form_register', 'post', $form_data['action']);
    if (!empty($form_data['message'])) {
        $form_register->addElement('html', $form_data['message'].'<br /><br />');
    }

    if ($usersCanCreateCourse) {
        $form_register->addElement('html', $form_data['button']);
    } else {
        $form_register->addElement('html', $form_data['go_button']);
    }

    $text_after_registration .= $form_register->returnForm();

    // Just in case
    Session::erase('course_redirect');
    Session::erase('exercise_redirect');
    Session::erase('session_redirect');
    Session::erase('only_one_course_session_redirect');

    if (CustomPages::enabled()) {
        CustomPages::display(
            CustomPages::REGISTRATION_FEEDBACK,
            array('info' => $text_after_registration)
        );
    } else {
        $tpl = new Template($tool_name);
        $tpl->assign('inscription_content', $content);
        $tpl->assign('text_after_registration', $text_after_registration);
        $tpl->assign('hide_header', $hideHeaders);
        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);
    }
} else {
    // Custom pages
    if (CustomPages::enabled()) {
        CustomPages::display(
            CustomPages::REGISTRATION, array('form' => $form)
        );
    } else {
        if (!api_is_anonymous()) {
            // Saving user to course if it was set.
            if (!empty($course_code_redirect)) {
                $course_info = api_get_course_info($course_code_redirect);
                if (!empty($course_info)) {
                    if (in_array(
                        $course_info['visibility'],
                        array(
                            COURSE_VISIBILITY_OPEN_PLATFORM,
                            COURSE_VISIBILITY_OPEN_WORLD
                        )
                    )
                    ) {
                        CourseManager::subscribe_user(
                            $user_id,
                            $course_info['code']
                        );
                    }
                }
            }
            CourseManager::redirectToCourse([]);
        }

        $tpl = new Template($tool_name);
        if ($termActivated !== false) {
            $tpl->assign(
                'inscription_header',
                Display::page_header($tool_name)
            );
        }
        $tpl->assign('inscription_content', $content);
        $tpl->assign('form', $form->returnForm());
        $tpl->assign('hide_header', $hideHeaders);

        $inscription = $tpl->get_template('auth/inscription.tpl');
        $tpl->display($inscription);
    }
}
