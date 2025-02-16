import * as esbuild from 'esbuild'
{{{esbuildImports}}}

await esbuild.build({
  /**
   * Defines all the "entry points" to be built. Entry points define the initial scripts to be executed.
   *
   * Most smaller applications would only need a single entry point, but it makes sense to split them according
   * to their own logically-separated group, to help separation of concern.
   */
  entryPoints: [
    'assets/app.ts'
  ],

  /**
   * Bundles imported dependencies into the file which references them.
   *
   * This allows for fewer, albeit larger, assets.
   */
  bundle: true,

  /**
   * Minifies the generated code instead of pretty-printing it.
   *
   * Pretty-printed code is easier to read in a console, but takes longer to download.
   */
  minify: true,

  /**
   * Defines the directory where all built assets are stored.
   *
   * This folder should not be checked out in your VCS, as it can always be rebuilt.
   */
  outdir: 'public/_build',

  /**
   * Defines optional plugins for esbuild. These plugins have to be installed and imported
   * before they can be used.
   *
   * Plugins can extend esbuild to allow for TailwindCSS, PostCSS, Markdown and others.
   */
  plugins: [
    {{{esbuildPlugins}}}
  ]
});