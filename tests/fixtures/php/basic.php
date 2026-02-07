<?php
// @expect: Hello world
// @expect: messages.welcome
// @expect: Please login
// @expect: Welcome :name

echo __('Hello world');
echo Lang::get('messages.welcome');
@lang('Please login')
echo __('Welcome :name', ['name' => 'John']);
