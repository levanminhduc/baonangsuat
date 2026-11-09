module.exports = {
  content: [
    "./views/**/*.php",
    "./components/**/*.php",
    "./public/assets/js/**/*.js",
    "./app/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        primary: "#143583",
        "primary-dark": "#0f2a66",
        success: "#4CAF50",
        warning: "#ff9800",
        danger: "#f44336"
      }
    }
  }
}
