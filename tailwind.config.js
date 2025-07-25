module.exports = {
  content: [
    "./public/**/*.php",
    "./includes/**/*.php",
    "./*.html",
  ],
  theme: {
    extend: {
      fontFamily: {
        inter: ['Inter', 'sans-serif'],
      },
      colors: {
        'blue-600': '#2563eb',
        'blue-700': '#1d4ed8',
        'green-50': '#f0fdf4',
        'green-200': '#dcfce7',
        'green-700': '#15803d',
        'green-800': '#14532d',
        'red-50': '#fef2f2',
        'red-200': '#fee2e2',
        'red-700': '#b91c1c',
        'red-800': '#991b1b',
        'gray-50': '#f9fafb',
        'gray-100': '#f3f4f6',
        'gray-200': '#e5e7eb',
        'gray-300': '#d1d5db',
        'gray-400': '#9ca3af',
        'gray-500': '#6b7280',
        'gray-600': '#4b5563',
        'gray-700': '#374151',
        'gray-800': '#1f2937',
        'gray-900': '#111827',
      }
    },
  },
  plugins: [],
}
