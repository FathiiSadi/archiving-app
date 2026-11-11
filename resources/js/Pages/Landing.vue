<script setup>
import { Head } from '@inertiajs/vue3';
import CompanyMark from '../Components/CompanyMark.vue';
import SectorFolder from '../Components/SectorFolder.vue';

defineProps({
    loginUrl: { type: String, required: true },
    contactEmail: { type: String, default: null },
});

const services = [
    {
        title: 'نظام مبنيّ على طريقة عملكم',
        body: 'نبدأ من جداولكم ونماذجكم الحالية، ونبني لكل قسم أو فريق شاشات إدخال قصيرة تناسب ما يسجّله فعلاً.',
    },
    {
        title: 'مؤشرات تُحسب وحدها',
        body: 'الحضور والمصروفات والإنجاز تُحسب من السجلات نفسها، وتُقارن بين الأشهر والأعوام دون جدول يدوي واحد.',
    },
    {
        title: 'ملفاتكم تبقى حيث هي',
        body: 'تبقى الفواتير والصور والعقود في Google Drive، ويُحفظ رابطها في السجل مع معاينة داخل النظام.',
    },
    {
        title: 'صلاحيات لكل شخص',
        body: 'يعدّل كل مسؤول سجلات قسمه وحده، وتطّلع الإدارة على الجميع وتقارن دون أن تعدّل.',
    },
];

const steps = [
    { title: 'نجلس مع فرقكم', body: 'نراجع ما تسجّلونه اليوم، ونتفق على ما يجب أن يُحفظ وما يجب أن يُقاس.' },
    { title: 'نعرض الشاشات قبل البناء', body: 'ترون نماذج الإدخال ولوحات المؤشرات وتعدّلونها قبل أن نبدأ البناء.' },
    { title: 'نطلق النظام وننقل بياناتكم', body: 'ننقل سجلاتكم السابقة من الجداول، فيبدأ النظام بتاريخكم كاملاً.' },
    { title: 'ندرّب ونبقى معكم', body: 'جلسة تدريب لكل مسؤول، ودعم مباشر بعد الإطلاق.' },
];

const questions = [
    {
        q: 'كم يستغرق بناء النظام؟',
        a: 'يعتمد على عدد الفرق وما تسجّله كل منها. نتفق على جدول زمني واضح بعد الجلسة الأولى.',
    },
    {
        q: 'أين تُحفظ بياناتنا؟',
        a: 'في قاعدة بيانات مخصّصة لجهتكم، مع نسخ احتياطية دورية. الملفات تبقى في حسابكم على Google Drive.',
    },
    {
        q: 'هل يعمل النظام على الهاتف؟',
        a: 'نعم. الواجهة عربية بالكامل وتتكيّف مع الشاشات الصغيرة، ليُسجَّل النشاط في مكانه.',
    },
    {
        q: 'هل نستطيع تعديل النظام لاحقاً؟',
        a: 'نعم. حين تتغيّر طريقة عملكم نضيف الحقول والفرق والمؤشرات الجديدة.',
    },
];
</script>

<template>
    <Head title="Archiving App" />

    <div class="min-h-screen overflow-x-hidden bg-board text-navy">
        <header class="border-b border-rule bg-sheet">
            <div class="container-page flex h-16 items-center justify-between gap-4">
                <a href="/" class="flex items-center gap-3">
                    <CompanyMark class="size-9" />
                    <span class="leading-tight">
                        <span class="block text-lg font-bold tracking-tight" dir="ltr">Archiving App</span>
                        <span class="block text-[11px] text-navy-faint">أنظمة أرشفة للجهات والفرق</span>
                    </span>
                </a>
                <nav class="flex items-center gap-6 text-sm">
                    <a href="#services" class="hidden text-navy-soft hover:text-navy md:inline">خدماتنا</a>
                    <a href="#process" class="hidden text-navy-soft hover:text-navy md:inline">طريقة عملنا</a>
                    <a href="#faq" class="hidden text-navy-soft hover:text-navy md:inline">أسئلة شائعة</a>
                    <a :href="loginUrl" class="rounded-lg bg-navy px-4 py-2 font-medium text-white transition hover:bg-navy/85">تسجيل الدخول</a>
                </nav>
            </div>
        </header>

        <main>
            <!-- Hero: the manila folder is the page's one memorable element. -->
            <section class="container-page grid gap-12 py-14 sm:py-20 lg:grid-cols-12 lg:items-center lg:gap-10">
                <div class="reveal lg:col-span-5">
                    <h1 class="text-[2.2rem] font-extrabold leading-[1.35] tracking-tight text-balance sm:text-[2.8rem] sm:leading-[1.3]">
                        نرتّب سجلات جهتك، ونحسب منها ما يهمّك.
                    </h1>
                    <p class="mt-6 max-w-lg text-[17px] leading-8 text-navy-soft">
                        نبني أنظمة أرشفة عربية للمبادرات والجمعيات والمدارس والشركات: كل نشاط وعقد وفاتورة في مكانه،
                        ومؤشرات تتحدّث وحدها بدل الجداول المتفرقة.
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-4">
                        <a href="#contact" class="inline-flex items-center rounded-lg bg-tape px-6 py-3.5 text-[15px] font-semibold text-white shadow-[0_10px_24px_-12px_rgb(140_28_43/0.8)] transition hover:bg-tape-deep">
                            اطلب نظاماً لجهتك
                        </a>
                        <a :href="loginUrl" class="text-[15px] font-medium text-navy-soft underline decoration-rule decoration-2 underline-offset-8 transition hover:text-navy hover:decoration-tape">
                            لديك حساب؟ سجّل الدخول
                        </a>
                    </div>
                </div>

                <div class="reveal lg:col-span-7 lg:ps-4" style="animation-delay: 120ms">
                    <SectorFolder />
                    <p class="mt-4 text-xs text-navy-faint">اختر نوع جهتك لترى ما يُؤرشف لها. الأمثلة توضيحية.</p>
                </div>
            </section>

            <!-- Services -->
            <section id="services" class="scroll-mt-8 border-y border-rule bg-sheet">
                <div class="container-page grid gap-10 py-20 sm:py-24 lg:grid-cols-3">
                    <div>
                        <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">خدماتنا</h2>
                        <p class="mt-3 max-w-xs text-sm leading-7 text-navy-soft">نظام واحد لكل جهة، مصمَّم حول فرقها وما تحتاج أن تعرفه.</p>
                    </div>
                    <dl class="grid gap-x-10 gap-y-9 sm:grid-cols-2 lg:col-span-2">
                        <div v-for="s in services" :key="s.title" class="border-s-[3px] border-tape ps-5">
                            <dt class="text-lg font-bold">{{ s.title }}</dt>
                            <dd class="mt-2 text-[15px] leading-7 text-navy-soft">{{ s.body }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <!-- Process: a real sequence, so it is numbered. -->
            <section id="process" class="scroll-mt-8 bg-navy text-white">
                <div class="container-page py-20 sm:py-24">
                    <h2 class="max-w-2xl text-3xl font-extrabold leading-snug tracking-tight text-balance sm:text-4xl sm:leading-snug">
                        من الجداول المتفرقة إلى نظام يعمل، في أربع خطوات
                    </h2>
                    <ol class="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8">
                        <li v-for="(s, i) in steps" :key="s.title" class="border-t border-white/15 pt-6">
                            <span class="numeral text-4xl font-extrabold text-manila">{{ (i + 1).toLocaleString('ar-EG') }}</span>
                            <h3 class="mt-4 text-lg font-bold">{{ s.title }}</h3>
                            <p class="mt-2 text-[15px] leading-7 text-white/70">{{ s.body }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- FAQ -->
            <section id="faq" class="scroll-mt-8 container-page grid gap-10 py-20 sm:py-24 lg:grid-cols-3">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">أسئلة شائعة</h2>
                <div class="divide-y divide-rule border-y border-rule lg:col-span-2">
                    <details v-for="item in questions" :key="item.q" class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-bold">
                            {{ item.q }}
                            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-sheet text-tape transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 max-w-2xl text-[15px] leading-7 text-navy-soft">{{ item.a }}</p>
                    </details>
                </div>
            </section>

            <!-- Contact -->
            <section id="contact" class="scroll-mt-8 container-page pb-20 sm:pb-24">
                <div class="rounded-2xl border-t-[6px] border-tape bg-sheet px-6 py-12 shadow-[0_0_0_1px_rgb(26_34_51/0.08)] sm:px-12 sm:py-16">
                    <h2 class="max-w-2xl text-3xl font-extrabold leading-snug tracking-tight text-balance sm:text-4xl sm:leading-snug">
                        هل سجلات جهتك موزّعة على جداول ومجموعات؟
                    </h2>
                    <p class="mt-4 max-w-xl text-[17px] leading-8 text-navy-soft">
                        أخبرنا كيف تعملون اليوم، ونقترح عليكم نظاماً يناسب فرقكم وما تريدون قياسه.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4">
                        <a
                            v-if="contactEmail"
                            :href="`mailto:${contactEmail}`"
                            class="inline-flex items-center rounded-lg bg-tape px-6 py-3.5 text-[15px] font-semibold text-white transition hover:bg-tape-deep"
                        >
                            راسلنا على <span class="ms-1.5" dir="ltr">{{ contactEmail }}</span>
                        </a>
                        <a :href="loginUrl" class="text-[15px] font-medium text-navy-soft underline decoration-rule decoration-2 underline-offset-8 transition hover:text-navy hover:decoration-tape">
                            لعملائنا: تسجيل الدخول
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-rule bg-sheet">
            <div class="container-page flex flex-col items-start justify-between gap-4 py-8 text-sm text-navy-faint sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <CompanyMark class="size-7" />
                    <span class="text-navy-soft"><span dir="ltr">Archiving App</span>، أنظمة أرشفة للجهات والفرق</span>
                </div>
                <span>© {{ new Date().getFullYear() }}</span>
            </div>
        </footer>
    </div>
</template>
