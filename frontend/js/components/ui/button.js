import { cva } from "class-variance-authority";
import { cn } from "../../lib/utils.js";

// shadcn-style Button (cva variants + Tailwind)
export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "bg-primary text-primary-foreground shadow hover:bg-primary/90",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        outline:
          "border border-input bg-background hover:bg-accent hover:text-accent-foreground",
        ghost: "hover:bg-accent hover:text-accent-foreground",
        destructive:
          "bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90",
      },
      size: {
        default: "h-8 px-4",
        sm: "h-7 px-3",
        lg: "h-10 px-6 text-sm",
        icon: "h-8 w-8",
      },
    },
    defaultVariants: { variant: "default", size: "default" },
  }
);

export function Button({
  text = "",
  variant = "default",
  size = "default",
  href = null,
  type = "button",
  className = "",
  icon = "",
  attrs = "",
} = {}) {
  const cls = cn(buttonVariants({ variant, size }), className);
  const inner = `${icon ? `<i data-lucide="${icon}" class="icon"></i>` : ""}<span>${text}</span>`;

  if (href) {
    return `<a href="${href}" class="${cls}" ${attrs}>${inner}</a>`;
  }
  return `<button type="${type}" class="${cls}" ${attrs}>${inner}</button>`;
}
