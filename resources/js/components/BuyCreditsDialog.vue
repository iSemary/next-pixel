<script setup lang="ts">
import { Coins, Loader2, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreditBalance } from '@/composables/useCreditBalance';

const PRESET_AMOUNTS = [5, 10, 25, 50] as const;

const { formattedBalance, isBuyCreditsOpen, isToppingUp, topUpCredits } =
    useCreditBalance();

const amountInput = ref<string>('10');

function selectPreset(amount: number): void {
    amountInput.value = String(amount);
}

async function handleSubmit(): Promise<void> {
    const parsed = parseFloat(amountInput.value);
    if (isNaN(parsed) || parsed < 1 || parsed > 500) {
        return;
    }
    await topUpCredits(parsed);
}
</script>

<template>
    <Dialog v-model:open="isBuyCreditsOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div
                    class="mb-2 flex h-10 w-10 items-center justify-center rounded-xl bg-primary/15 text-primary"
                >
                    <Coins class="h-5 w-5" />
                </div>
                <DialogTitle class="font-bricolage text-lg">
                    Buy credits
                </DialogTitle>
                <DialogDescription class="text-xs leading-relaxed">
                    Add simulated USD credits to your account balance. Current
                    balance:
                    <strong class="font-mono text-foreground">{{
                        formattedBalance
                    }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4 pt-2" @submit.prevent="handleSubmit">
                <div class="space-y-2">
                    <Label
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Quick Select
                    </Label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="preset in PRESET_AMOUNTS"
                            :key="preset"
                            type="button"
                            class="flex h-10 cursor-pointer items-center justify-center rounded-lg border font-mono text-sm font-semibold transition-all"
                            :class="
                                Number(amountInput) === preset
                                    ? 'border-primary bg-primary text-primary-foreground shadow-xs'
                                    : 'border-border/80 bg-background hover:bg-muted/60'
                            "
                            @click="selectPreset(preset)"
                        >
                            ${{ preset }}
                        </button>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="topup-amount" class="text-xs font-medium">
                        Custom amount (USD, $1 – $500)
                    </Label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 font-mono text-sm text-muted-foreground"
                            >$</span
                        >
                        <Input
                            id="topup-amount"
                            v-model="amountInput"
                            type="number"
                            step="0.01"
                            min="1"
                            max="500"
                            class="pl-7 font-mono"
                            required
                        />
                    </div>
                </div>

                <p
                    class="rounded-lg border border-border/70 bg-muted/30 px-3 py-2 text-[11px] text-muted-foreground"
                >
                    Simulated purchase for testing — no real card or payment is
                    charged.
                </p>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="isToppingUp"
                        @click="isBuyCreditsOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="
                            isToppingUp ||
                            isNaN(Number(amountInput)) ||
                            Number(amountInput) < 1 ||
                            Number(amountInput) > 500
                        "
                        class="gap-2 font-semibold"
                    >
                        <Loader2
                            v-if="isToppingUp"
                            class="h-4 w-4 animate-spin"
                        />
                        <Sparkles v-else class="h-4 w-4" />
                        <span>
                            {{
                                isToppingUp
                                    ? 'Adding credits...'
                                    : `Add $${Number(amountInput || 0).toFixed(2)}`
                            }}
                        </span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
