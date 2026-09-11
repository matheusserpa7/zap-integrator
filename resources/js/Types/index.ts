export type AuthUser = {
    public_id: string;
    name: string;
    email: string;
    initials: string;
    is_platform_admin: boolean;
};

export type SharedWorkspace = {
    public_id: string;
    name: string;
    max_instances: number;
};

export type ReverbPublicConfig = {
    key: string | null;
    host: string | null;
    port: number | null;
    scheme: string;
};

export type SharedProps = {
    workspace: SharedWorkspace | null;
    reverb: ReverbPublicConfig;
    auth: {
        user: AuthUser | null;
    };
    flash: {
        success: string | null;
        error: string | null;
        plainTextToken: string | null;
        webhookSecret: string | null;
    };
};
