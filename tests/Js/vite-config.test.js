import { afterEach, expect, test } from 'bun:test';
import viteConfig from '../../vite.config.js';

const originalAppUrl = process.env.APP_URL;

afterEach(() => {
    if (originalAppUrl === undefined) {
        delete process.env.APP_URL;
    } else {
        process.env.APP_URL = originalAppUrl;
    }
});

test.each([
    ['https://my-app.localhost:9443', 'my-app.localhost', 'wss', 9443],
    ['http://my-app.localhost:8080', 'my-app.localhost', 'ws', 8080],
    ['https://my-app.example.com', 'my-app.example.com', 'wss', 443],
    ['http://my-app.example.com', 'my-app.example.com', 'ws', 80],
])('routes Vite and HMR through the public origin %s', (appUrl, host, protocol, port) => {
    process.env.APP_URL = appUrl;

    const { server } = viteConfig({ command: 'serve', mode: 'development' });

    expect(server).toMatchObject({
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: appUrl,
        allowedHosts: [host],
        hmr: { host, protocol, clientPort: port },
    });
});

test('requires an application URL for the development server', () => {
    process.env.APP_URL = '';

    expect(() => viteConfig({ command: 'serve', mode: 'development' })).toThrow('Set APP_URL in .env');
});

test('configures the asset build without an application URL', () => {
    process.env.APP_URL = '';

    expect(viteConfig({ command: 'build', mode: 'production' }).server).toBeUndefined();
});
