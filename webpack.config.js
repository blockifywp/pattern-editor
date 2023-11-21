const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = ( env ) => {
	return {
		...defaultConfig,

		module: {
			...defaultConfig.module,
		},

		entry: {
			...defaultConfig.entry,
			patterns: './src/patterns.tsx',
			export: './src/export.tsx',
		},

		devServer: {
			...defaultConfig.devServer,
			hot: false,
			liveReload: false,
		},
	};
};
