<template>
  <!-- @expect-none -->
  <!-- These should NOT be detected as translation keys -->

  <div>{{ something.toString('test') }}</div>
  <div @click="$emit('event-name')">Click</div>
</template>
