const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const RemoveEmptyScriptsPlugin = require("webpack-remove-empty-scripts");
const DependencyExtractionWebpackPlugin = require("@wordpress/dependency-extraction-webpack-plugin");
const path = require("path");
module.exports = (env) => {
  const appMode = env && env.mode ? env.mode : "development";
  return [
    {
      entry: {
        "admin-reorder": "./src/scss/admin-reorder.scss",
      },
      // With webpack `mode: "production"`, css-loader resolves `tailwindcss/theme.css` / `preflight.css` as separate modules that skip PostCSS, so output diverges from `npm run start` and admin styles break. Use `development` for this compiler when `appMode` is `production`. Do not pass `--mode` on the CLI for the whole run; it overrides this and reproduces the bug (see package.json `build` script).
      mode: "production" === appMode ? "development" : appMode,
      devtool: "production" === appMode ? false : "source-map",
      output: {
        path: path.resolve(__dirname, "dist/css"),
        clean: true,
      },
      module: {
        rules: [
          {
            test: /\.scss$/,
            exclude: /(node_modules|bower_components)/,
            use: [
              {
                loader: MiniCssExtractPlugin.loader,
              },
              {
                loader: "css-loader",
                options: {
                  sourceMap: "production" === appMode ? false : true,
                },
              },
              {
                loader: "postcss-loader",
              },
              {
                loader: "resolve-url-loader",
              },
              {
                loader: "sass-loader",
                options: {
                  sourceMap: "production" === appMode ? false : true,
                },
              },
            ],
          },
          {
            test: /\.css$/,
            use: [
              {
                loader: MiniCssExtractPlugin.loader,
              },
              {
                loader: "css-loader",
                options: {
                  sourceMap: "production" === appMode ? false : true,
                },
              },
            ],
          },
          {
            test: /\.(woff2?|ttf|otf|eot|svg)$/,
            include: [path.resolve(__dirname, "fonts")],
            exclude: /(node_modules|bower_components)/,
            type: "asset/resource",
          },
        ],
      },
      plugins: [new RemoveEmptyScriptsPlugin(), new MiniCssExtractPlugin()],
    },
    {
      entry: {
        "admin-reorder-posts": "./src/ts/react/views/reorder/index.tsx",
      },
      resolve: {
        extensions: [".ts", ".tsx", ".js", ".jsx"],
        alias: {
          "@": path.resolve(__dirname, "src"),
          "@sr": path.resolve(__dirname, "src/ts"),
        },
      },
      mode: appMode,
      devtool: "production" === appMode ? false : "source-map",
      output: {
        filename: "[name].js",
        sourceMapFilename: "[file].map[query]",
        assetModuleFilename: "fonts/[name][ext]",
        path: path.resolve(__dirname, "dist/js"),
        clean: true,
      },
      module: {
        rules: [
          {
            test: /\.(js|jsx|ts|tsx)$/,
            exclude: /(node_modules|bower_components)/,
            loader: "babel-loader",
            options: {
              presets: [
                "@babel/preset-env",
                ["@babel/preset-react", { runtime: "automatic" }],
                [
                  "@babel/preset-typescript",
                  { allExtensions: true, isTSX: true },
                ],
              ],
              plugins: [
                "@babel/plugin-transform-class-properties",
                "@babel/plugin-transform-arrow-functions",
              ],
            },
          },
        ],
      },
      plugins: [
        new RemoveEmptyScriptsPlugin(),
        new DependencyExtractionWebpackPlugin(),
      ],
    },
  ];
};
