import defaultTheme from "tailwindcss/defaultTheme";

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    preset: [],
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        // Add mary
        "./vendor/robsontenorio/mary/src/View/Components/**/*.php",
    ],
    safelist: [
        "text-error",
        "text-warning",
        "text-success",
        "text-info",
        "text-gray-500",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [require("@tailwindcss/typography"), require("daisyui")],
    daisyui: {
        themes: ["light", "synthwave", "cyberpunk", "retro", "valentine"],
    },
};
