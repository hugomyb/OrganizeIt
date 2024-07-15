/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js'
  ],
  theme: {
    extend: {
        colors: {
            'blue-100': 'rgb(219, 234, 254)',
        },
    },
  },
  plugins: [],
}

