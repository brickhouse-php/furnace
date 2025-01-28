import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/views/**/*.html.php",
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

