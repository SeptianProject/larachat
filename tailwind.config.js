/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#2D9664',
        secondary: '#56D3B3',
        dark: '#1E1E1E',
        orange: '#FFAF3B',
        light: '#F1F2F2',
        danger: '#FF7E67',

        grey: '#7C7C7C',
        greyy: '#BBBBBB',
      },
    },
  },
  plugins: [],
}