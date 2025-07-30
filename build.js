import * as esbuild from 'esbuild'

const isDev = process.argv.includes('--dev')

async function compile(options) {
    const context = await esbuild.context(options)

    if (isDev) {
        await context.watch()
    } else {
        await context.rebuild()
        await context.dispose()
    }
}

const defaultOptions = {
    define: {
        'process.env.NODE_ENV': isDev ? `'development'` : `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    target: ['es2020'],
    minify: !isDev,
    plugins: [{
        name: 'watchPlugin',
        setup: function (build) {
            build.onStart(() => {
                console.log(`Build started at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
            })

            build.onEnd((result) => {
                if (result.errors.length > 0) {
                    console.log(`Build failed at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`, result.errors)
                } else {
                    console.log(`Build finished at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
                }
            })
        }
    }],
}

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/editorjs/index.js'],
    outfile: './resources/js/dist/components/editorjs.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/document/index.js'],
    outfile: './resources/js/dist/components/document.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/chunked-file-upload.js'],
    outfile: './resources/js/dist/components/chunked-file-upload.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/mentionable/index.js'],
    outfile: './resources/js/dist/components/mentionable.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/voice-recorder/index.js'],
    outfile: './resources/js/dist/components/voice-recorder.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/audio-player/index.js'],
    outfile: './resources/js/dist/components/audio-player.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/video-recorder/index.js'],
    outfile: './resources/js/dist/components/video-recorder.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./resources/js/components/design-annotation/index.js'],
    outfile: './resources/js/dist/components/design-annotation.js',
})
