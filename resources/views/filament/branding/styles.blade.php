<style>
    :root {
        --mshp-green: #047857;
        --mshp-green-dark: #065f46;
        --mshp-gold: #d97706;
        --mshp-red: #b91c1c;
    }

    .fi-sidebar-header,
    .fi-topbar {
        border-bottom: 1px solid rgba(4, 120, 87, 0.14);
    }

    .fi-logo img,
    .fi-sidebar-header img {
        object-fit: contain;
    }

    .mshp-admin-header {
        background: linear-gradient(90deg, var(--mshp-green-dark), var(--mshp-green));
        border-bottom: 3px solid var(--mshp-gold);
        color: #ffffff;
    }

    .mshp-admin-header__inner,
    .mshp-admin-footer__inner {
        margin: 0 auto;
        max-width: 96rem;
        padding: 0.75rem 1rem;
    }

    .mshp-admin-header__inner {
        align-items: center;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
    }

    .mshp-admin-header__identity {
        align-items: center;
        display: flex;
        gap: 0.875rem;
        min-width: 0;
    }

    .mshp-admin-header__flag,
    .mshp-admin-header__logo {
        background: #ffffff;
        border-radius: 0.375rem;
        height: 3rem;
        object-fit: contain;
        padding: 0.25rem;
        width: 3rem;
    }

    .mshp-admin-header__title {
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }

    .mshp-admin-header__subtitle {
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.75rem;
        line-height: 1.35;
        margin: 0.125rem 0 0;
    }

    .mshp-admin-header__tagline {
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.75rem;
        font-weight: 600;
        text-align: right;
        white-space: nowrap;
    }

    .mshp-admin-footer {
        border-top: 1px solid rgba(4, 120, 87, 0.16);
        color: #475569;
        font-size: 0.75rem;
    }

    .mshp-admin-footer__inner {
        align-items: center;
        display: flex;
        gap: 0.75rem;
        justify-content: space-between;
    }

    .mshp-admin-footer strong {
        color: #064e3b;
        font-weight: 700;
    }

    @media (max-width: 640px) {
        .mshp-admin-header__inner,
        .mshp-admin-footer__inner {
            align-items: flex-start;
            flex-direction: column;
        }

        .mshp-admin-header__tagline {
            text-align: left;
            white-space: normal;
        }
    }
</style>
