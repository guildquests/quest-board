// Characters without look-alikes (no 0/O, 1/l/I), so a password read out loud or
// handed over on paper is not misread.
const LOWER = 'abcdefghjkmnpqrstuvwxyz';
const UPPER = 'ACDEFGHJKLMNPQRTUVWXY';
const DIGITS = '34679';
const SYMBOLS = '!@#$%&*?+=-';

const ALL = LOWER + UPPER + DIGITS + SYMBOLS;

/** A random index below `max`, without modulo bias. */
function randomBelow(max: number): number {
    const limit = Math.floor(0x1_0000_0000 / max) * max;
    const buffer = new Uint32Array(1);

    do {
        crypto.getRandomValues(buffer);
    } while (buffer[0] >= limit);

    return buffer[0] % max;
}

function pick(characters: string): string {
    return characters[randomBelow(characters.length)];
}

/**
 * A starting password that passes the strictest rule the server uses (12+
 * characters with lower case, upper case, a digit, and a symbol).
 */
export function generatePassword(length = 16): string {
    const characters = [
        pick(LOWER),
        pick(UPPER),
        pick(DIGITS),
        pick(SYMBOLS),
        ...Array.from({ length: Math.max(length, 12) - 4 }, () => pick(ALL)),
    ];

    // Fisher-Yates shuffle, so the guaranteed characters are not always first.
    for (let i = characters.length - 1; i > 0; i--) {
        const j = randomBelow(i + 1);
        [characters[i], characters[j]] = [characters[j], characters[i]];
    }

    return characters.join('');
}

/**
 * Plain-language help text for the server's password rule, which arrives as a
 * Laravel rules string such as "minlength: 8;" or
 * "minlength: 12; required: lower; required: upper; required: digit; required: [-];".
 */
export function describePasswordRules(rules: string): string {
    const minimum = /minlength:\s*(\d+)/.exec(rules)?.[1] ?? '8';
    const strict = rules.includes('required:');

    return strict
        ? `At least ${minimum} characters, with upper and lower case letters, a number, and a symbol.`
        : `At least ${minimum} characters.`;
}
