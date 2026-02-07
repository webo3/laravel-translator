<?php
// @expect-none
// $t() and .t() should not match in PHP files

$t("some string");
$this->t("method call");
