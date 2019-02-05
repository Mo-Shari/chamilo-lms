<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSCourse SOAP service.
 *
 * @package chamilo.webservices
 */
require_once __DIR__.'/webservice_course.php';
require_once __DIR__.'/soap.php';

/**
 * Configures the WSCourse SOAP service.
 */
$s = WSSoapServer::singleton();

$s->wsdl->addComplexType(
    'course_id',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_field_name' => ['name' => 'course_id_field_name', 'type' => 'xsd:string'],
        'course_id_value' => ['name' => 'course_id_value', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'course_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_value' => ['name' => 'course_id_value', 'type' => 'xsd:string'],
        'result' => ['name' => 'result', 'type' => 'tns:result'],
    ]
);

$s->wsdl->addComplexType(
    'course_result_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_result[]']],
    'tns:course_result'
);

$s->register(
    'WSCourse.DeleteCourse',
    ['secret_key' => 'xsd:string', 'course_id_field_name' => 'xsd:string', 'course_id_value' => 'xsd:string'],
    [],
    'urn:WSService', // namespace
    'urn:WSService#WSCourse.DeleteCourse', // soapaction
    'rpc', // style
    'encoded', // use
    'Delete a course in chamilo'                   // documentation
);

$s->register(
    'WSCourse.DeleteCourses',
    ['secret_key' => 'xsd:string', 'courses' => 'tns:course_id[]'],
    ['return' => 'tns:course_result_array']
);

$s->wsdl->addComplexType(
    'course',
    'complexType',
    'struct',
    'all',
    '',
    [
        'id' => ['name' => 'id', 'type' => 'xsd:int'],
        'code' => ['name' => 'code', 'type' => 'xsd:string'],
        'title' => ['name' => 'title', 'type' => 'xsd:string'],
        'language' => ['name' => 'language', 'type' => 'xsd:string'],
        'visibility' => ['name' => 'visibility', 'type' => 'xsd:int'],
        'category_name' => ['name' => 'category_name', 'type' => 'xsd:string'],
        'number_students' => ['name' => 'number_students', 'type' => 'xsd:int'],
        'external_course_id' => ['name' => 'external_course_id', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'course_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course[]']],
    'tns:course'
);

$s->register(
    'WSCourse.ListCourses',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'visibilities' => 'xsd:string',
    ],
    ['return' => 'tns:course_array']
);

$s->register(
    'WSCourse.SubscribeUserToCourse',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'status' => 'xsd:int',
    ]
);

$s->register(
    'WSCourse.UnsubscribeUserFromCourse',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
    ]
);

$s->wsdl->addComplexType(
    'course_description',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_desc_id' => ['name' => 'course_desc_id', 'type' => 'xsd:int'],
        'course_desc_title' => ['name' => 'course_desc_title', 'type' => 'xsd:string'],
        'course_desc_content' => ['name' => 'course_desc_content', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'course_description_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_description[]']],
    'tns:course_description'
);

$s->register(
    'WSCourse.GetCourseDescriptions',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
    ],
    ['return' => 'tns:course_description_array']
);

$s->register(
    'WSCourse.EditCourseDescription',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'course_desc_id' => 'xsd:int',
        'course_desc_title' => 'xsd:string',
        'course_desc_content' => 'xsd:string',
    ]
);
