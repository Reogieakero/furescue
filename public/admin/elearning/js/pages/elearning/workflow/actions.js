import { toast } from "/js/components/ui/toast.js";
import { confirmDialog } from "/js/components/ui/dialog.js";
import * as api from "../../../api.js";
import { state, loadModules, resetEditor } from "../state.js";
import { rerenderAll } from "../components.js";
import { readEditorForm } from "../components/editor.js";
import { CATEGORIES, emptyEditor } from "../components/util.js";

const CATEGORY_KEYS = CATEGORIES.map((c) => c.key);

function moduleTitle(id) {
  const row = state.modules.find((m) => m.id === id);
  if (row && row.title) return row.title;
  if (state.editor && state.editor.id === id && state.editor.title) return state.editor.title;
  return "this module";
}

async function reloadLibrary() {
  resetEditor();
  try {
    await loadModules();
  } catch (err) {
    toast(err.message || "Could not refresh modules.", { type: "error" });
  }
  rerenderAll();
}

export async function openNew() {
  state.view = "editor";
  state.editor = emptyEditor();
  rerenderAll();
}

export async function openEdit(id) {
  if (!id) return;
  try {
    const mod = await api.fetchModule(id);
    state.view = "editor";
    state.editor = {
      id: mod.id,
      title: mod.title || "",
      category: mod.category || "general_care",
      content_body: mod.content_body || "",
      published_status: mod.published_status === "published" ? "published" : "draft",
    };
    rerenderAll();
  } catch (err) {
    toast(err.message || "Could not load that module.", { type: "error" });
  }
}

export function closeEditor() {
  resetEditor();
  rerenderAll();
}

function validate(fields) {
  if (!fields.title) return "Title is required.";
  if (fields.title.length > 150) return "Title must be 150 characters or fewer.";
  if (!CATEGORY_KEYS.includes(fields.category)) return "Choose a valid category.";
  if (!fields.content_body.trim()) return "Content body is required.";
  if (fields.content_body.length > 20000) return "Content body must be 20,000 characters or fewer.";
  return "";
}

export async function runSave(event) {
  if (event) event.preventDefault();
  if (state.saving) return;
  const fields = readEditorForm();
  const error = validate(fields);
  if (error) {
    toast(error, { type: "error" });
    return;
  }
  state.saving = true;
  try {
    if (state.editor && state.editor.id) {
      await api.updateModule(state.editor.id, fields);
      toast("Module saved.", { type: "success" });
    } else {
      await api.createModule(fields);
      toast("Module created.", { type: "success" });
    }
    await reloadLibrary();
  } catch (err) {
    toast(err.message || "Could not save the module.", { type: "error" });
  } finally {
    state.saving = false;
  }
}

function editorPayload(status) {
  if (state.view !== "editor" || !state.editor || !state.editor.id) {
    return { published_status: status };
  }
  const fields = readEditorForm();
  const error = validate(fields);
  if (error) return { error };
  return { ...fields, published_status: status };
}

export async function runPublish(id) {
  if (!id) return;
  const payload = editorPayload("published");
  if (payload.error) {
    toast(payload.error, { type: "error" });
    return;
  }
  const ok = await confirmDialog({
    title: "Publish module",
    message: `Publish “${moduleTitle(id)}”? Residents will see it on the Learning Hub.`,
    confirmText: "Publish",
    run: () => api.updateModule(id, payload),
  });
  if (!ok) return;
  toast("Module published.", { type: "success" });
  await reloadLibrary();
}

export async function runUnpublish(id) {
  if (!id) return;
  const payload = editorPayload("draft");
  if (payload.error) {
    toast(payload.error, { type: "error" });
    return;
  }
  const ok = await confirmDialog({
    title: "Unpublish module",
    message: `Unpublish “${moduleTitle(id)}”? It will leave the resident Learning Hub until you publish again.`,
    danger: true,
    confirmText: "Unpublish",
    run: () => api.updateModule(id, payload),
  });
  if (!ok) return;
  toast("Module unpublished.", { type: "success" });
  await reloadLibrary();
}
