<script setup>
import { computed } from 'vue';

const props = defineProps({
    semester: { type: String, default: null },
    snapshot: { type: Object, default: null },
    target: { type: Number, required: true },
});

const fmt = (v) => (v === null || v === undefined ? '—' : `${Number(v).toLocaleString('en-US', { maximumFractionDigits: 1 })}٪`);
const overallMet = computed(() => props.snapshot?.archive !== null && props.snapshot?.archive >= props.target);
</script>

<template>
    <figure class="relative">
        <!-- Offset sheet behind the card: a stack of register pages -->
        <div class="absolute inset-0 translate-x-3 translate-y-3 rounded-2xl border border-line bg-paper-deep" aria-hidden="true" />

        <div class="relative overflow-hidden rounded-2xl border border-line bg-card shadow-[0_1px_0_rgb(19_37_31/0.04),0_24px_48px_-28px_rgb(19_37_31/0.35)]">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-medium tracking-wide text-ink-faint">سجل الفصل الحالي</p>
                    <p class="mt-0.5 font-display text-lg text-ink">{{ semester ?? 'لم يُحدَّد فصل بعد' }}</p>
                </div>
                <span class="rounded-full border border-line px-3 py-1 text-xs text-ink-soft">اكتمال الأرشفة</span>
            </header>

            <div v-if="snapshot" class="ledger-lines">
                <ul>
                    <li v-for="team in snapshot.teams" :key="team.key" class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-2 border-b border-line px-5 py-4 last:border-b-0 sm:px-6">
                        <div class="flex items-baseline gap-2">
                            <span class="text-[15px] font-medium text-ink">{{ team.name }}</span>
                            <span class="numeral text-xs text-ink-faint">{{ team.records }} سجل</span>
                        </div>
                        <span class="numeral text-sm font-semibold" :class="team.rate !== null && team.rate >= target ? 'text-mint' : 'text-ink-soft'">{{ fmt(team.rate) }}</span>

                        <div class="relative col-span-2 h-1.5 rounded-full bg-paper-deep">
                            <div
                                class="bar-fill absolute inset-y-0 right-0 rounded-full"
                                :class="team.rate !== null && team.rate >= target ? 'bg-mint' : 'bg-brass'"
                                :style="{ width: `${Math.min(team.rate ?? 0, 100)}%` }"
                            />
                            <!-- target marker -->
                            <span class="absolute -top-1 h-3.5 w-px bg-ink/40" :style="{ right: `${target}%` }" aria-hidden="true" />
                        </div>
                    </li>
                </ul>
            </div>

            <div v-else class="px-6 py-12 text-center text-sm text-ink-soft">
                ستظهر مؤشرات الأرشفة هنا بمجرد إضافة الفصل الدراسي وتسجيل أول السجلات.
            </div>

            <footer v-if="snapshot" class="flex items-center justify-between gap-4 border-t border-line bg-paper/60 px-5 py-4 sm:px-6">
                <p class="text-sm text-ink-soft">
                    الإجمالي مقابل الهدف
                    <span class="numeral font-medium text-ink">{{ target }}٪</span>
                </p>
                <p class="numeral font-display text-2xl" :class="overallMet ? 'text-mint' : 'text-brass'">{{ fmt(snapshot.archive) }}</p>
            </footer>
        </div>
    </figure>
</template>
