import { createIcons, icons } from "lucide";
import { toast } from "../../js/components/ui/toast.js";
import { showLoader, hideLoader } from "../../js/components/ui/loader.js";
import { proofFromUpload, uploadProof } from "./api.js";

const MAX_FILES = 8;
const MAX_BYTES = 10 * 1024 * 1024;

function setError(form, message) {
  const slot = form.querySelector("#proof-error");
  if (!slot) return;
  const span = slot.querySelector("span");
  if (span) span.textContent = message || "";
  slot.hidden = !message;
}

function chosenFiles(input) {
  return Array.from(input.files || []);
}

function validate(files) {
  if (!files.length) return "Choose at least one photo from this device.";
  if (files.length > MAX_FILES) return `Up to ${MAX_FILES} files at a time.`;
  const oversized = files.find((file) => file.size > MAX_BYTES);
  if (oversized) return `"${oversized.name}" is larger than 10 MB.`;
  return "";
}

function paintNames(form, files) {
  const names = form.querySelector("#proof-names");
  if (!names) return;
  if (!files.length) {
    names.hidden = true;
    names.textContent = "";
    return;
  }
  names.hidden = false;
  names.textContent = files.map((file) => file.name).join(", ");
}

export function bindProofForm(root, { caseId, onUploaded } = {}) {
  const form = root.querySelector("#proof-form");
  if (!form || form.dataset.bound) return;
  form.dataset.bound = "1";

  const input = form.querySelector("#proof-files");
  input?.addEventListener("change", () => {
    setError(form, "");
    paintNames(form, chosenFiles(input));
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const files = chosenFiles(input);
    const problem = validate(files);
    if (problem) {
      setError(form, problem);
      return;
    }
    const submit = form.querySelector("#proof-submit");
    if (submit) submit.disabled = true;
    showLoader("Uploading proof…");
    try {
      const payload = await uploadProof(caseId, files);
      toast("Proof uploaded.", { type: "success" });
      form.reset();
      paintNames(form, []);
      setError(form, "");
      if (onUploaded) await onUploaded(proofFromUpload(payload));
    } catch (err) {
      setError(form, err.message || "Could not upload proof.");
      toast(err.message || "Could not upload proof.", { type: "error" });
    } finally {
      hideLoader();
      if (submit) submit.disabled = false;
      createIcons({ icons });
    }
  });
}
