<?php
/**
 * strange english to work with my getErrors() override
 * :attribute => input name
 * :params => rule parameters ( eg: :params(0) = 10 of max_length(10) )
 */
return array(
    'required' => ':attribute field is required',
    'integer' => ':attribute field is must be an integer',
    'float' => ':attribute field is must be a float',
    'numeric' => ':attribute field is must be numeric',
    'email' => ':attribute field is invalid',
    'alpha' => ':attribute field is must be only letters',
    'alpha_numeric' => ':attribute field is must be only numbers and letters',
    'ip' => ':attribute field is invalid',
    'url' => ':attribute field is invalid',
    'max_length' => ':attribute field is can be maximum :params(0) character long',
    'min_length' => ':attribute field is must be minimum :params(0) character long',
    'exact_length' => ':attribute field is must be :params(0) character long',
    'equals' => ':attribute field is must match :params(0)',
    'unique' => ':attribute field is must be unique'
);
