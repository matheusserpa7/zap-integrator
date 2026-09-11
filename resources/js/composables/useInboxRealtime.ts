import { onMounted, onUnmounted, ref, watch, type Ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import type { ConnectionStatus } from 'laravel-echo';
import { createWorkspaceEcho } from '@/echo';
import { inboxMessageEvents } from '@/reverb';
import type { SharedProps } from '@/Types';

type PollControl = {
    start: () => void;
    stop: () => void;
};

export function useInboxRealtime(poll: PollControl): Ref<boolean> {
    const page = usePage<SharedProps>();
    const live = ref(false);
    let leaveChannel: (() => void) | null = null;
    let unbindConnection: (() => void) | null = null;

    function reloadInbox(): void {
        router.reload({ only: ['conversations', 'selected', 'messages'] });
    }

    function applyConnectionStatus(status: ConnectionStatus): void {
        if (status === 'connected') {
            live.value = true;
            poll.stop();

            return;
        }

        live.value = false;
        poll.start();
    }

    function subscribe(workspacePublicId: string): void {
        const echo = createWorkspaceEcho(page.props.reverb);

        if (!echo) {
            applyConnectionStatus('disconnected');

            return;
        }

        const channel = echo.private(`workspaces.${workspacePublicId}`);

        for (const eventName of inboxMessageEvents) {
            channel.listen(eventName, reloadInbox);
        }

        leaveChannel = () => {
            echo.leave(`workspaces.${workspacePublicId}`);
        };

        applyConnectionStatus(echo.connectionStatus());
        unbindConnection = echo.connector.onConnectionChange(applyConnectionStatus);
    }

    function teardown(): void {
        unbindConnection?.();
        unbindConnection = null;
        leaveChannel?.();
        leaveChannel = null;
        live.value = false;
    }

    onMounted(() => {
        const workspacePublicId = page.props.workspace?.public_id;

        if (workspacePublicId) {
            subscribe(workspacePublicId);

            return;
        }

        applyConnectionStatus('disconnected');
    });

    onUnmounted(() => {
        teardown();
        poll.stop();
    });

    watch(
        () => page.props.workspace?.public_id,
        (workspacePublicId, previous) => {
            if (workspacePublicId === previous) {
                return;
            }

            teardown();

            if (workspacePublicId) {
                subscribe(workspacePublicId);

                return;
            }

            applyConnectionStatus('disconnected');
        },
    );

    return live;
}
