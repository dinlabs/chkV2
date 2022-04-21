const path = require('path');
const Encore = require('@symfony/webpack-encore');

const syliusBundles = path.resolve(__dirname, 'vendor/sylius/sylius/src/Sylius/Bundle/');
const uiBundleScripts = path.resolve(syliusBundles, 'UiBundle/Resources/private/js/');
const uiBundleResources = path.resolve(syliusBundles, 'UiBundle/Resources/private/');

// Shop config
Encore
  .setOutputPath('public/build/shop/')
  .setPublicPath('/build/shop')
  .addEntry('shop-entry', './assets/shop/entry.js')
  .disableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableSassLoader();

const shopConfig = Encore.getWebpackConfig();

shopConfig.resolve.alias['sylius/ui'] = uiBundleScripts;
shopConfig.resolve.alias['sylius/ui-resources'] = uiBundleResources;
shopConfig.resolve.alias['sylius/bundle'] = syliusBundles;
shopConfig.name = 'shop';

Encore.reset();

// Admin config
Encore
  .setOutputPath('public/build/admin/')
  .setPublicPath('/build/admin')
  .addEntry('admin-entry', './assets/admin/entry.js')
  .disableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .enableSassLoader();

const adminConfig = Encore.getWebpackConfig();

adminConfig.resolve.alias['sylius/ui'] = uiBundleScripts;
adminConfig.resolve.alias['sylius/ui-resources'] = uiBundleResources;
adminConfig.resolve.alias['sylius/bundle'] = syliusBundles;
adminConfig.externals = Object.assign({}, adminConfig.externals, { window: 'window', document: 'document' });
adminConfig.name = 'admin';

//module.exports = [shopConfig, adminConfig];


Encore.reset();

// Chullanka config
Encore
  .setOutputPath('public/build/chullanka/')
  .setPublicPath('/build/chullanka')
  .addEntry('chullanka-entry', './assets/chullanka/entry.js')
  .copyFiles({
    from: './assets/chullanka/img/emails',
    // optional target path, relative to the output dir
    to: 'images/emails/[path][name].[ext]',
  })
  .disableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableVersioning(Encore.isProduction())

const chullankaConfig = Encore.getWebpackConfig();
chullankaConfig.resolve.alias['sylius/ui'] = uiBundleScripts;
chullankaConfig.resolve.alias['sylius/ui-resources'] = uiBundleResources;
chullankaConfig.resolve.alias['sylius/bundle'] = syliusBundles;
//chullankaConfig.externals = Object.assign({}, chullankaConfig.externals, { window: 'window', document: 'document' });
chullankaConfig.name = 'chullanka';

module.exports = [shopConfig, adminConfig, chullankaConfig];

//run: yarn encore dev
