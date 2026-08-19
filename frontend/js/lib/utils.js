import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

// shadcn/ui className merge helper
export function cn(...inputs) {
  return twMerge(clsx(inputs));
}
