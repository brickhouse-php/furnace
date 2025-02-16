import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.php.html",
  ],
  theme: {
    extend: {
      colors: {
        accent: colors.orange,
      }
    },
  },
  plugins: [],
}
