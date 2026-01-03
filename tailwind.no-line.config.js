const base = require("./tailwind.config.js")

module.exports = {
  ...base,
  theme: {
    ...base.theme,
    extend: {
      ...base.theme.extend,
      colors: {
        ...base.theme.extend.colors,
        primary: "#2196F3"
      }
    }
  }
}
