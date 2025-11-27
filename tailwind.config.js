/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Livewire/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: 'hsl(210, 100%, 97%)',
          100: 'hsl(210, 100%, 94%)',
          200: 'hsl(210, 100%, 88%)',
          300: 'hsl(210, 100%, 80%)',
          400: 'hsl(210, 100%, 70%)',
          500: 'hsl(210, 100%, 60%)',
          600: 'hsl(210, 90%, 50%)',
          700: 'hsl(210, 85%, 45%)',
          800: 'hsl(210, 80%, 40%)',
          900: 'hsl(210, 75%, 30%)',
          950: 'hsl(210, 70%, 20%)',
        },
        accent: {
          50: 'hsl(280, 100%, 97%)',
          100: 'hsl(280, 100%, 94%)',
          200: 'hsl(280, 100%, 88%)',
          300: 'hsl(280, 100%, 80%)',
          400: 'hsl(280, 100%, 70%)',
          500: 'hsl(280, 90%, 60%)',
          600: 'hsl(280, 85%, 50%)',
          700: 'hsl(280, 80%, 45%)',
          800: 'hsl(280, 75%, 40%)',
          900: 'hsl(280, 70%, 30%)',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out',
        'slide-up': 'slideUp 0.5s ease-out',
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(20px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
      },
    },
  },
  plugins: [],
}
