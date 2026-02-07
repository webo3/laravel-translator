<template>
  <!-- @expect: Vue underscore key -->
  <!-- @expect: Vue dollar t -->
  <!-- @expect: Vue dot t -->
  <!-- @expect: Vue backtick key -->

  <div>{{ __('Vue underscore key') }}</div>
  <div>{{ $t('Vue dollar t') }}</div>
  <div>{{ i18n.t('Vue dot t') }}</div>
  <div>{{ __(`Vue backtick key`) }}</div>
</template>
