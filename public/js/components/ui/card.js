import { cn } from "../../lib/utils.js";

export function Card({ className = "", children = "" } = {}) {
  return `<div class="${cn(
    "rounded-xl border bg-card text-card-foreground shadow",
    className
  )}">${children}</div>`;
}

export function CardHeader({ className = "", children = "" } = {}) {
  return `<div class="${cn("flex flex-col space-y-1.5 p-6", className)}">${children}</div>`;
}

export function CardTitle({ className = "", children = "", as = "h3" } = {}) {
  return `<${as} class="${cn(
    "font-semibold leading-none tracking-tight",
    className
  )}">${children}</${as}>`;
}

export function CardDescription({ className = "", children = "" } = {}) {
  return `<p class="${cn("text-sm text-muted-foreground", className)}">${children}</p>`;
}

export function CardContent({ className = "", children = "" } = {}) {
  return `<div class="${cn("p-6 pt-0", className)}">${children}</div>`;
}

export function CardFooter({ className = "", children = "" } = {}) {
  return `<div class="${cn("flex items-center p-6 pt-0", className)}">${children}</div>`;
}
