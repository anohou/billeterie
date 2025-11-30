<script setup>
import { reactive } from 'vue'
import SettingsLayout from '@/Layouts/SettingsLayout.vue'
const props = defineProps({ station: Object })
const form = reactive({ name: props.station?.name||'', code: props.station?.code||'', city: props.station?.city||'', address: props.station?.address||'', phone: props.station?.phone||'', active: props.station?.active ?? true })
</script>
<template>
  <SettingsLayout>
    <template #header><h2 class='text-xl font-semibold text-green-700'>{{ props.station ? 'Edit' : 'New' }} Station</h2></template>
    <form class='p-6' :action="props.station ? `/admin/stations/${props.station.id}` : '/admin/stations'" method='post'>
      <input type='hidden' name='_token' :value="document.querySelector('meta[name=csrf-token]')?.content"/>
      <template v-if="props.station"><input type='hidden' name='_method' value='PUT'/></template>
      <div class='grid gap-4 md:grid-cols-2'>
        <label>Name<input class='border rounded w-full p-2' name='name' v-model='form.name'/></label>
        <label>Code<input class='border rounded w-full p-2' name='code' v-model='form.code'/></label>
        <label>City<input class='border rounded w-full p-2' name='city' v-model='form.city'/></label>
        <label>Address<input class='border rounded w-full p-2' name='address' v-model='form.address'/></label>
        <label>Phone<input class='border rounded w-full p-2' name='phone' v-model='form.phone'/></label>
        <label class='flex items-center gap-2'><input type='checkbox' name='active' :checked='form.active'/> Active</label>
      </div>
      <div class='mt-6'><button class='px-4 py-2 bg-green-600 text-white rounded'>Save</button></div>
    </form>
  </SettingsLayout>
</template>
