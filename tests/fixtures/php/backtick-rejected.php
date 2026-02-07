<?php
// @expect-none
// Backticks in PHP are shell execution, not translation strings

echo __(`shell command`);
