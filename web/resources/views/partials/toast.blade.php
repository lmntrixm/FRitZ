<div class="toast-wrap" :class="{ up: $store.cmp.items.length > 0 && !!document.querySelector('[data-cmpbar]') }"
     role="status" aria-live="polite">
  <template x-if="$store.toast.msg">
    <div class="toast">
      <span x-show="$store.toast.act === 'list'"><x-icon i="checkCircle" s="20" /></span>
      <span x-show="$store.toast.act !== 'list'"><x-icon i="info" s="20" /></span>
      <span style="flex:1" x-text="$store.toast.msg"></span>
      <button class="lnk" type="button" x-show="$store.toast.act === 'list'" @click="$store.inq.show()">مشاهده لیست</button>
    </div>
  </template>
</div>
