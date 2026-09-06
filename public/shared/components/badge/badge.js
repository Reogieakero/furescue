import { cva } from "class-variance-authority";
import { cn } from "/assets/js/lib/utils.js";

const badgeVariants = cva(
  "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2",
  {
    variants: {
      variant: {
        default: "border-transparent bg-primary text-primary-foreground",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground",
        outline: "text-foreground",
        destructive:
          "border-transparent bg-destructive text-destructive-foreground",
        success:
          "border-transparent bg-primary/10 text-primary",
        accent: "border-transparent bg-accent text-accent-foreground",
      },
    },
    defaultVariants: { variant: "default" },
  }
);

export function Badge({ text = "", variant = "default", className = "", icon = "" } = {}) {
  const cls = cn(badgeVariants({ variant }), className);
  return `<span class="${cls}">${icon ? `<i data-lucide="${icon}" class="badge-icon"></i>` : ""}${text}</span>`;
}
