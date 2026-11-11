<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ArchiveRegister from '../Components/ArchiveRegister.vue';
import BrandMark from '../Components/BrandMark.vue';

const props = defineProps({
    semester: { type: String, default: null },
    snapshot: { type: Object, default: null },
    panelUrl: { type: String, required: true },
    loginUrl: { type: String, required: true },
    isSignedIn: { type: Boolean, default: false },
    archiveTarget: { type: Number, required: true },
});

const entryUrl = computed(() => (props.isSignedIn ? props.panelUrl : props.loginUrl));
const entryLabel = computed(() => (props.isSignedIn ? 'المتابعة إلى لوحة فريقك' : 'الدخول إلى لوحة الفريق'));

const num = (v, digits = 0) => (v === null || v === undefined ? '—' : Number(v).toLocaleString('en-US', { maximumFractionDigits: digits }));

const figures = computed(() => {
    const s = props.snapshot;
    if (!s) return [];
    return [
        { label: 'أنشطة منفّذة', value: num(s.activitiesCompleted), note: 'هذا الفصل' },
        { label: 'نسبة الحضور', value: s.attendanceRate === null ? '—' : `${num(s.attendanceRate, 1)}٪`, note: 'الفعلي مقابل المتوقع' },
        { label: 'صفحات مسمّعة', value: num(s.pagesRecited), note: `في ${num(s.circles)} حلقات` },
        { label: 'إنجاز مستويات التجويد', value: s.tajweedCompletion === null ? '—' : `${num(s.tajweedCompletion, 1)}٪`, note: `الهدف ${s.tajweedTarget}٪` },
    ];
});

const teams = [
    {
        index: '٠١',
        name: 'فريق الأنشطة',
        summary: 'من الفكرة المعتمدة إلى الفاتورة الأخيرة.',
        records: ['الأنشطة وحالتها والحضور المتوقع والفعلي', 'المشتريات والمورّدون مع روابط الفواتير', 'الضيوف والمتحدثون وحالة دعوتهم'],
        indicators: ['نسبة الحضور', 'المصروف مقابل السقف', 'متوسط التقييم'],
    },
    {
        index: '٠٢',
        name: 'فريق التحفيظ',
        summary: 'كل جلسة تسميع محسوبة بالصفحة.',
        records: ['سجل المعلمين والحلقات وسعتها', 'جلسات التسميع: حفظ جديد ومراجعة', 'تقييم الحلقات على خمسة معايير'],
        indicators: ['الصفحات التراكمية', 'جودة الحلقات', 'استقرار المعلمين'],
    },
    {
        index: '٠٣',
        name: 'فريق التجويد',
        summary: 'المستوى يكتمل حين تكتمل دروسه.',
        records: ['المدرّسون والمشرفون والشعب والمستويات', 'المحاضرات والدروس العملية والحضور', 'درجات التسميع والاختبار والأهلية'],
        indicators: ['الإنجاز مقابل الهدف', 'الالتزام بالحضور', 'الإتقان العملي'],
    },
    {
        index: '٠٤',
        name: 'فريق الشؤون الداخلية',
        summary: 'الفائض والعجز يُحسبان، لا يُكتبان.',
        records: ['الأنشطة الداخلية من القائمة المعتمدة', 'الإيرادات والتكاليف الفعلية', 'المورّدون والأماكن وتقييمهم'],
        indicators: ['صافي الفائض/العجز', 'التكلفة على الفرد', 'القائمة البيضاء والسوداء'],
    },
];

const steps = [
    { title: 'يسجّل قائد الفريق', body: 'نماذج قصيرة بأزرار بدل القوائم الطويلة، صُممت ليُؤرشف النشاط أو الجلسة في أقل من ثلاث دقائق.' },
    { title: 'يُرفق رابطاً لا ملفاً', body: 'الفواتير والصور والكشوفات تبقى في Google Drive، ويُحفظ رابطها فقط مع معاينة مباشرة داخل النظام.' },
    { title: 'تُحسب المؤشرات وحدها', body: 'الحضور والمصروفات والفائض والجودة والإنجاز — تُحدَّث فوراً وتُفلتر حسب الفصل والعام.' },
];
</script>

<template>
    <Head title="الرئيسية" />

    <div class="min-h-screen overflow-x-hidden">
        <!-- Top bar -->
        <header class="border-b border-line">
            <div class="container-page flex h-16 items-center justify-between">
                <a href="/" class="flex items-center gap-3">
                    <BrandMark class="size-10" />
                    <span class="leading-tight">
                        <span class="block font-display text-lg text-ink">أرشيف دال</span>
                        <span class="block text-[11px] text-ink-faint">نظام أرشفة فرق المبادرة</span>
                    </span>
                </a>
                <a :href="entryUrl" class="rounded-lg border border-line bg-card px-4 py-2 text-sm font-medium text-ink transition hover:border-forest/40 hover:text-forest">
                    {{ isSignedIn ? 'لوحة التحكم' : 'تسجيل الدخول' }}
                </a>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section class="container-page grid gap-12 py-14 sm:py-20 lg:grid-cols-12 lg:items-center lg:gap-10">
                <div class="reveal lg:col-span-6">
                    <p class="inline-flex items-center gap-2 text-sm text-ink-soft">
                        <span class="h-px w-8 bg-mint" aria-hidden="true" />
                        الأنشطة · التحفيظ · التجويد · الشؤون الداخلية
                    </p>
                    <h1 class="mt-5 font-display text-[2.4rem] leading-[1.25] text-ink sm:text-5xl sm:leading-[1.2]">
                        ذاكرةُ المبادرة،
                        <br />
                        <span class="text-forest">مرتّبةٌ ومحسوبة.</span>
                    </h1>
                    <p class="mt-6 max-w-xl text-[17px] leading-8 text-ink-soft">
                        مكانٌ واحد يجمع سجلات الفرق الأربعة بدل الجداول المتفرقة، ويحوّلها تلقائياً إلى مؤشرات
                        تقارن الفصول والأعوام وتقيّم المعلمين والمورّدين — بدقة تكفي لاتخاذ القرار وتوثيق كل دينار.
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-4">
                        <a :href="entryUrl" class="inline-flex items-center gap-2 rounded-xl bg-forest px-6 py-3.5 text-[15px] font-medium text-paper shadow-[0_10px_24px_-12px_rgb(0_67_45/0.7)] transition hover:bg-forest-deep">
                            {{ entryLabel }}
                            <svg class="size-4 rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" /></svg>
                        </a>
                        <a href="#how" class="text-[15px] font-medium text-ink-soft underline decoration-line decoration-2 underline-offset-8 transition hover:text-ink hover:decoration-brass">كيف يعمل النظام</a>
                    </div>
                </div>

                <div class="reveal lg:col-span-6 lg:ps-6" style="animation-delay: 120ms">
                    <ArchiveRegister :semester="semester" :snapshot="snapshot" :target="archiveTarget" />
                </div>
            </section>

            <!-- Figures strip -->
            <section v-if="figures.length" class="border-y border-line bg-card">
                <dl class="container-page grid grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(f, i) in figures"
                        :key="f.label"
                        class="border-line py-7 ps-1 sm:py-9"
                        :class="[i % 2 === 0 ? 'border-e pe-4' : 'ps-5 lg:ps-6', i < 2 ? 'border-b lg:border-b-0' : '', i === 1 ? 'lg:border-e lg:pe-4' : '', i === 2 ? 'lg:ps-6' : '']"
                    >
                        <dt class="text-sm text-ink-soft">{{ f.label }}</dt>
                        <dd class="numeral mt-2 font-display text-3xl text-ink sm:text-4xl">{{ f.value }}</dd>
                        <dd class="mt-1 text-xs text-ink-faint">{{ f.note }}</dd>
                    </div>
                </dl>
                <p class="container-page pb-5 text-xs text-ink-faint">أرقام مجمّعة للفصل الحالي ({{ semester }}) — لا تُعرض هنا أي بيانات شخصية أو مالية.</p>
            </section>

            <!-- Teams -->
            <section class="container-page py-20 sm:py-24">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-sm font-medium text-brass">أربعة فرق، أرشيف واحد</p>
                        <h2 class="mt-2 font-display text-3xl text-ink sm:text-4xl">ما الذي يُؤرشف، وما الذي يُقاس</h2>
                    </div>
                    <p class="max-w-sm text-sm leading-7 text-ink-soft">لكل فريق لوحة متابعة مستقلة، ولإدارة المبادرة لوحة تقارن الفصول والأعوام.</p>
                </div>

                <div class="mt-12 grid gap-px overflow-hidden rounded-2xl border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
                    <article v-for="team in teams" :key="team.name" class="flex flex-col bg-card p-6 sm:p-7">
                        <span class="font-display text-sm text-brass">{{ team.index }}</span>
                        <h3 class="mt-3 font-display text-xl text-ink">{{ team.name }}</h3>
                        <p class="mt-2 text-sm leading-6 text-ink-soft">{{ team.summary }}</p>
                        <ul class="mt-6 space-y-2.5 border-t border-line pt-5 text-sm leading-6 text-ink">
                            <li v-for="r in team.records" :key="r" class="flex gap-2.5">
                                <span class="mt-2.5 size-1 shrink-0 rounded-full bg-mint" aria-hidden="true" />
                                {{ r }}
                            </li>
                        </ul>
                        <div class="mt-auto flex flex-wrap gap-1.5 pt-6">
                            <span v-for="k in team.indicators" :key="k" class="rounded-md bg-paper px-2 py-1 text-xs text-ink-soft">{{ k }}</span>
                        </div>
                    </article>
                </div>
            </section>

            <!-- How it works -->
            <section id="how" class="scroll-mt-8 bg-forest-deep text-paper">
                <div class="container-page py-20 sm:py-24">
                    <p class="text-sm font-medium text-paper/60">كيف يعمل</p>
                    <h2 class="mt-2 max-w-2xl font-display text-3xl sm:text-4xl">ثلاث خطوات، بلا حساب يدوي واحد.</h2>
                    <ol class="mt-14 grid gap-10 md:grid-cols-3 md:gap-8">
                        <li v-for="(s, i) in steps" :key="s.title" class="border-t border-paper/15 pt-6">
                            <span class="numeral font-display text-4xl text-brass-soft">{{ i + 1 }}</span>
                            <h3 class="mt-4 text-lg font-semibold">{{ s.title }}</h3>
                            <p class="mt-2 text-[15px] leading-7 text-paper/70">{{ s.body }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- Principles -->
            <section class="container-page grid gap-10 py-20 sm:py-24 lg:grid-cols-3">
                <div>
                    <h2 class="font-display text-3xl text-ink">مبادئ لا تُساوَم</h2>
                    <p class="mt-3 text-sm leading-7 text-ink-soft">صُمم النظام حول متطلبات المبادرة في الدقة والخصوصية وسهولة الاستخدام.</p>
                </div>
                <dl class="grid gap-8 sm:grid-cols-2 lg:col-span-2">
                    <div>
                        <dt class="font-semibold text-ink">إدخال لقائد الفريق فقط</dt>
                        <dd class="mt-2 text-sm leading-7 text-ink-soft">كل قائد يحرّر سجلات فريقه وحده، والإدارة تطّلع وتقارن دون أن تعدّل.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink">أرقام الهواتف محمية</dt>
                        <dd class="mt-2 text-sm leading-7 text-ink-soft">مشفّرة في قاعدة البيانات، ومخفية جزئياً لكل من لا يحتاجها.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink">كل المبالغ بالدينار الأردني</dt>
                        <dd class="mt-2 text-sm leading-7 text-ink-soft">مطابقة المصروفات مع الميزانية المعتمدة والسقف التقديري لكل فصل.</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-ink">يعمل على الهاتف والحاسوب</dt>
                        <dd class="mt-2 text-sm leading-7 text-ink-soft">واجهة عربية كاملة تتكيّف مع الشاشات الصغيرة لتسجيل النشاط في مكانه.</dd>
                    </div>
                </dl>
            </section>
        </main>

        <footer class="border-t border-line">
            <div class="container-page flex flex-col items-start justify-between gap-4 py-8 text-sm text-ink-faint sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <BrandMark class="size-7" />
                    <span class="text-ink-soft">أرشيف دال — نظام أرشفة فرق المبادرة</span>
                </div>
                <a :href="entryUrl" class="font-medium text-ink-soft transition hover:text-forest">{{ isSignedIn ? 'لوحة التحكم' : 'تسجيل الدخول' }} ←</a>
            </div>
        </footer>
    </div>
</template>
