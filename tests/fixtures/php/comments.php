<?php
// @expect: real key
// @expect: after block
// @expect: Visit http://example.com for info

// __('commented out single line')
__('real key');

/* __('commented out block') */
__('after block');

/*
 * __('commented out multiline')
 */

__('Visit http://example.com for info');
