<template>
  <!-- @expect: Editing translations for: {lang}. Default language values are shown as placeholders. -->
  <!-- @expect: Simple key -->

  <div>
    {{
        $t('Editing translations for: {lang}. Default language values are shown as placeholders.', {
            lang: localeName(activeLocale)
        })
    }}
  </div>
  <div>{{ $t('Simple key') }}</div>
</template>
