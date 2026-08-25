export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function stampCls(status) {
  if (status === "pending") return "stamp--coral";
  if (status === "approved" || status === "completed") return "stamp--accent";
  return "stamp--muted";
}

export function applicantName(a) {
  return (a && a.applicant_name) || "";
}

export function animalName(a) {
  return (a && a.animal_name) || "";
}
