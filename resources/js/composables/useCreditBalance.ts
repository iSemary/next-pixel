import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { postJson } from '@/lib/http';
import { topUp as topUpRoute } from '@/routes/credits';

interface TopUpResponse {
    credit_balance: string;
    added_amount: string;
    message: string;
}

const creditBalance = ref<string>('0.000000000');
const isBuyCreditsOpen = ref<boolean>(false);
const isToppingUp = ref<boolean>(false);

export function useCreditBalance() {
    const numericBalance = computed(() => {
        const parsed = parseFloat(creditBalance.value);
        return isNaN(parsed) ? 0 : parsed;
    });

    const formattedBalance = computed(() => {
        const val = numericBalance.value;
        if (val === 0) {
            return '$0.00';
        }
        if (val > 0 && val < 0.01) {
            return `$${val.toFixed(4)}`;
        }
        // Show 2 decimals for standard dollar balances, up to 4 if fractional cents exist
        const four = val.toFixed(4);
        if (four.endsWith('00')) {
            return `$${val.toFixed(2)}`;
        }
        return `$${four.replace(/0+$/, '')}`;
    });

    const syncBalance = (nextBalance: string | null | undefined): void => {
        if (
            nextBalance !== null &&
            nextBalance !== undefined &&
            nextBalance !== ''
        ) {
            creditBalance.value = String(nextBalance);
        }
    };

    const openBuyCredits = (): void => {
        isBuyCreditsOpen.value = true;
    };

    const topUpCredits = async (amount: number): Promise<boolean> => {
        if (isToppingUp.value || amount < 1 || amount > 500) {
            return false;
        }

        isToppingUp.value = true;

        try {
            const response = await postJson<TopUpResponse>(
                topUpRoute.url(),
                { amount },
                { defaultErrorMessage: 'Failed to add credits' },
            );

            syncBalance(response.credit_balance);
            isBuyCreditsOpen.value = false;
            toast.success(response.message || 'Credits added to your balance.');

            return true;
        } catch (err: unknown) {
            const msg =
                err instanceof Error ? err.message : 'Failed to add credits';
            toast.error(msg);
            return false;
        } finally {
            isToppingUp.value = false;
        }
    };

    return {
        creditBalance,
        numericBalance,
        formattedBalance,
        isBuyCreditsOpen,
        isToppingUp,
        syncBalance,
        openBuyCredits,
        topUpCredits,
    };
}
