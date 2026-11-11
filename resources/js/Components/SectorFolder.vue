<script setup>
import { ref } from 'vue';

// One manila tab per kind of organisation we build for. Each shows sample entries and what the
// organisation learns from them — the folder is the company page's one interactive moment.
const sectors = [
    {
        key: 'initiatives',
        label: 'المبادرات',
        title: 'مبادرة شبابية بأربعة فرق',
        entries: [
            { name: 'ملتقى شبابي، حضر ١٤٢ من ١٢٠ متوقعاً', date: '١٢ تشرين الأول', state: 'مكتمل' },
            { name: 'شراء ضيافة مع رابط الفاتورة', date: '١٢ تشرين الأول', state: 'مطابق' },
            { name: 'دعوة متحدث ضيف', date: '٢٠ تشرين الأول', state: 'بانتظار الرد' },
        ],
        insight: 'نسبة الحضور والمصروف مقابل الميزانية لكل نشاط، فصلاً بفصل.',
    },
    {
        key: 'charities',
        label: 'الجمعيات الخيرية',
        title: 'جمعية توزّع طروداً شهرية',
        entries: [
            { name: 'توزيع طرود على ٣٨٠ أسرة', date: '٣ آذار', state: 'مكتمل' },
            { name: 'تبرع عيني من مورّد معتمد', date: '٥ آذار', state: 'مسجّل' },
            { name: 'زيارة ميدانية لحي جديد', date: '٩ آذار', state: 'مجدولة' },
        ],
        insight: 'التكلفة على الأسرة الواحدة، ومقارنة المورّدين بالسعر والالتزام.',
    },
    {
        key: 'schools',
        label: 'المدارس والمراكز',
        title: 'مركز تعليمي بعشر حلقات',
        entries: [
            { name: 'حلقة الفجر: ٤ صفحات حفظ جديد', date: '١ تشرين الثاني', state: 'مسجّل' },
            { name: 'تقييم الحلقة على خمسة معايير', date: '٢ تشرين الثاني', state: '٤٫٢ من ٥' },
            { name: 'غياب معلم وتعويضه', date: '٤ تشرين الثاني', state: 'مُعالج' },
        ],
        insight: 'تقدّم كل حلقة، وجودة التدريس، واستقرار المعلمين.',
    },
    {
        key: 'companies',
        label: 'الشركات',
        title: 'شركة بثلاثة أقسام',
        entries: [
            { name: 'عقد صيانة سنوي مع مورّد', date: '١٨ أيار', state: 'ساري' },
            { name: 'محضر اجتماع الإدارة الشهري', date: '٣١ أيار', state: 'مؤرشف' },
            { name: 'طلب شراء أجهزة للقسم المالي', date: '٢ حزيران', state: 'قيد الموافقة' },
        ],
        insight: 'العقود التي تقترب نهايتها، والمصروف لكل قسم، وقرارات كل اجتماع.',
    },
];

const active = ref(0);
const tabs = ref([]);

function select(index, focus = false) {
    active.value = (index + sectors.length) % sectors.length;
    if (focus) tabs.value[active.value]?.focus();
}

// RTL: the next tab sits to the left.
function onKey(event) {
    const moves = { ArrowLeft: 1, ArrowRight: -1, Home: -active.value, End: sectors.length - 1 - active.value };
    if (!(event.key in moves)) return;
    event.preventDefault();
    select(active.value + moves[event.key], true);
}
</script>

<template>
    <div>
        <div role="tablist" aria-label="نوع الجهة" class="flex items-end gap-1 overflow-x-auto ps-4 sm:ps-6" @keydown="onKey">
            <button
                v-for="(s, i) in sectors"
                :id="`tab-${s.key}`"
                :key="s.key"
                :ref="(el) => (tabs[i] = el)"
                type="button"
                role="tab"
                :aria-selected="active === i"
                :aria-controls="`panel-${s.key}`"
                :tabindex="active === i ? 0 : -1"
                class="folder-tab shrink-0 whitespace-nowrap rounded-t-lg px-4 pb-2.5 pt-3 text-sm text-navy transition-colors sm:px-5"
                :class="active === i ? 'bg-manila font-bold' : 'bg-manila-soft text-navy-soft hover:bg-manila/70'"
                @click="select(i)"
            >
                {{ s.label }}
            </button>
        </div>

        <div
            v-for="(s, i) in sectors"
            v-show="active === i"
            :id="`panel-${s.key}`"
            :key="s.key"
            role="tabpanel"
            :aria-labelledby="`tab-${s.key}`"
            class="folder-panel rounded-xl border-t-[6px] border-manila bg-sheet p-5 sm:p-7"
        >
            <p class="text-sm text-navy-faint">مثال</p>
            <h3 class="mt-1 text-xl font-bold text-navy">{{ s.title }}</h3>

            <ul class="mt-5 divide-y divide-rule border-y border-rule">
                <li v-for="e in s.entries" :key="e.name" class="grid h-12 grid-cols-[1fr_auto] items-center gap-3 text-sm">
                    <span class="truncate text-navy">{{ e.name }}</span>
                    <span class="flex items-center gap-3">
                        <span class="hidden text-xs text-navy-faint sm:inline">{{ e.date }}</span>
                        <span class="rounded bg-board px-2 py-0.5 text-xs font-medium text-navy">{{ e.state }}</span>
                    </span>
                </li>
            </ul>

            <div class="mt-6 rounded-lg bg-navy px-4 py-3.5 text-[15px] leading-7 text-white">
                <span class="text-manila">ما ستعرفه من هذه السجلات: </span>{{ s.insight }}
            </div>
        </div>
    </div>
</template>

<style scoped>
.folder-panel {
    box-shadow: 0 0 0 1px rgb(26 34 51 / 0.08), 0 28px 56px -34px rgb(26 34 51 / 0.45);
}

@media (prefers-reduced-motion: no-preference) {
    .folder-panel {
        animation: file-in 0.26s cubic-bezier(0.2, 0.7, 0.2, 1);
    }
}

@keyframes file-in {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: none; }
}
</style>
