/** Usuario con sesión iniciada (lo comparte HandleInertiaRequests). */
export type User = {
    id: number;
    nombre: string;
    rol: string | null;
};

export type Auth = {
    user: User | null;
};
