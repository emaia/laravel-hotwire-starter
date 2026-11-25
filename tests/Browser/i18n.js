import ptBR from '../../lang/pt_BR.json' with { type: 'json' };

const locale = (process.env.PLAYWRIGHT_TEST_LOCALE || process.env.APP_LOCALE || 'pt_BR')
    .replace('-', '_')
    .toLowerCase();

export function t(key) {
    return locale === 'pt_br' ? (ptBR[key] ?? key) : key;
}
