{{-- Inquiry list (M-Inquiry): items and quantities, optional name/project, message preview, send on WhatsApp. --}}
<div x-show="$store.inq.open" x-cloak @keydown.escape.window="$store.inq.close()">
  <div class="scrim" @click="$store.inq.close()"
       x-transition:enter="fade-enter" x-transition:enter-start="fade-from" x-transition:leave="fade-leave" x-transition:leave-end="fade-to-out"></div>
  <div class="sheet full" role="dialog" aria-modal="true" aria-labelledby="inq-h" x-trap.noscroll.inert="$store.inq.open"
       x-transition:enter="rise-enter" x-transition:enter-start="rise-from" x-transition:leave="rise-leave" x-transition:leave-end="rise-to-out">
    <span class="grab"></span>
    <div class="sheet-h">
      <div style="display:flex;align-items:baseline;gap:8px"><h2 class="t-h2" id="inq-h">لیست استعلام</h2><span class="t-c mut3" x-text="FA($store.inq.count) + ' قلم'"></span></div>
      <button class="ib" type="button" aria-label="بستن" @click="$store.inq.close()"><x-icon i="x" /></button>
    </div>

    <template x-if="!$store.inq.count && !$store.inq.sent">
      <div class="empty" style="flex:1;justify-content:center">
        <span class="ico"><x-icon i="inquiry" s="32" /></span>
        <h3 class="t-h2">لیست استعلام خالی است</h3>
        <p class="t-s mut">محصولات مورد نظرتان را اضافه کنید تا قیمت همه را یکجا در واتساپ استعلام بگیرید.</p>
        <a class="btn btn-p" style="margin-top:8px" href="{{ route('home') }}#cats" @click="$store.inq.close()">مشاهده محصولات</a>
      </div>
    </template>

    <template x-if="$store.inq.count && !$store.inq.sent">
      <div style="display:contents">
        <div class="sheet-b">
          <template x-for="it in $store.inq.items" :key="it.id">
            <div style="display:flex;gap:12px;padding:14px 0">
              <a class="ph" :href="it.url" style="width:64px;height:64px;flex:none" :aria-label="it.name">
                <template x-if="it.img"><img :src="it.img" alt=""></template>
                <template x-if="!it.img"><svg class="art" width="58" height="58" viewBox="0 0 200 200" aria-hidden="true"><use :href="'#art-' + it.art"/></svg></template>
              </a>
              <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:6px">
                <b style="font-size:14px;line-height:1.6" x-text="it.name"></b>
                <span class="code" x-text="it.code"></span>
                <div style="display:flex;align-items:center;justify-content:space-between">
                  <div class="qty" role="group" :aria-label="'تعداد ' + it.name">
                    <button type="button" aria-label="افزایش" @click="$store.inq.inc(it.id)"><x-icon i="plus" s="16" sw="2" /></button>
                    <b x-text="FA(it.qty)" aria-live="polite"></b>
                    <button type="button" aria-label="کاهش" @click="$store.inq.dec(it.id)" :disabled="it.qty <= 1"><x-icon i="minus" s="16" sw="2" /></button>
                  </div>
                  <button class="ib" type="button" aria-label="حذف از لیست" @click="$store.inq.remove(it.id)"><x-icon i="trash" s="20" /></button>
                </div>
              </div>
            </div>
          </template>
          <div style="display:flex;flex-direction:column;gap:14px;padding-top:18px">
            <div><label class="lab" for="inq-nm">نام شما <span class="mut3" style="font-weight:500">(اختیاری)</span></label>
              <input id="inq-nm" class="inp" placeholder="مثلاً علی رضایی" autocomplete="name" :value="$store.inq.name" @input="$store.inq.setName($event.target.value)"></div>
            <div><label class="lab" for="inq-pj">نام یا توضیح پروژه <span class="mut3" style="font-weight:500">(اختیاری)</span></label>
              <textarea id="inq-pj" class="inp" rows="2" placeholder="مثلاً ویلای دو طبقه، لواسان" :value="$store.inq.project" @input="$store.inq.setProject($event.target.value)"></textarea></div>
            <div>
              <span class="lab">پیش‌نمایش پیام</span>
              <div class="msg" x-text="$store.inq.message"></div>
            </div>
          </div>
        </div>
        <div class="sheet-f" style="flex-direction:column;gap:8px">
          <button class="btn btn-p btn-lg btn-w" type="button" @click="$store.inq.send()"><x-icon i="chat" s="20" sw="1.8" />ارسال استعلام در واتساپ</button>
          <p class="t-c mut3" style="text-align:center">پیام در واتساپ باز می‌شود و پیش از ارسال قابل ویرایش است.</p>
        </div>
      </div>
    </template>

    <template x-if="$store.inq.sent">
      <div class="empty" style="flex:1;justify-content:center">
        <span class="ico" style="background:var(--acS);color:var(--tx)"><x-icon i="checkCircle" s="34" /></span>
        <h3 class="t-h2">استعلام به واتساپ منتقل شد</h3>
        <p class="t-s mut">پس از ارسال پیام، کارشناسان FRitZ قیمت و زمان تأمین را برایتان می‌فرستند. لیست شما تا ارسال بعدی ذخیره می‌ماند.</p>
        <div style="display:flex;gap:10px;margin-top:8px">
          <a class="btn btn-p" href="{{ route('home') }}" @click="$store.inq.sent = false; $store.inq.close()">بازگشت به کاتالوگ</a>
          <button class="btn btn-s" type="button" @click="$store.inq.sent = false">ویرایش لیست</button>
        </div>
      </div>
    </template>
  </div>
</div>
