const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = ( env ) => {
	return {
		...defaultConfig,

		module: {
			...defaultConfig.module,
		},

		entry: {
			...defaultConfig.entry,
			index: './src/index.tsx',
			patterns: './src/patterns.tsx',
		},

		devServer: {
			...defaultConfig.devServer,
			hot: false,
			liveReload: false,
		},
	};
};
