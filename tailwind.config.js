/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js'
  ],
  theme: {
    extend: {
        // add bg-blue-100 background color on focus
        colors: {
            'blue-100': 'rgb(219, 234, 254)',
        },
    },
  },
  plugins: [],
}

