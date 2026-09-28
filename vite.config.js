import fs from "node:fs";
import path from "node:path";
import { defineConfig } from "vite";
import tailwindcss from "@tailwindcss/vite";

function notFoundPage() {
  const root = path.resolve(".");
  const pagePath = path.join(root, "404.html");

  return {
    name: "not-found-page",
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        const url = req.url?.split("?")[0] || "";
        if (
          url.startsWith("/@") ||
          url.startsWith("/node_modules") ||
          url.startsWith("/assets") ||
          url.startsWith("/css") ||
          url.startsWith("/js") ||
          url.includes(".")
        ) {
          return next();
        }

        const filePath = path.join(root, url === "/" ? "index.html" : url);
        if (fs.existsSync(filePath) || fs.existsSync(`${filePath}.html`)) {
          return next();
        }

        if (!fs.existsSync(pagePath)) return next();

        res.statusCode = 404;
        res.setHeader("Content-Type", "text/html; charset=utf-8");
        res.end(fs.readFileSync(pagePath));
      });
    },
  };
}

export default defineConfig({
  plugins: [tailwindcss(), notFoundPage()],
  server: {
    host: "127.0.0.1",
    port: 5173,
    open: false,
    strictPort: true,
    hmr: {
      host: "127.0.0.1",
      port: 5173,
    },
    watch: {
      ignored: ["**/node_modules/**", "**/dist/**"],
    },
  },
});
