<?php
// @expect: First
// @expect: Second
// @expect: Third

echo __('First') . ' ' . __("Second");
Lang::get('Third');
