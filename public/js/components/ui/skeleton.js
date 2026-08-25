function block({ w = "100%", h = "1rem", rounded = "0.5rem", className = "" } = {}) {
  return `<span class="skeleton ${className}" style="width:${w};height:${h};border-radius:${rounded}"></span>`;
}

export function Skeleton(opts) {
  return block(opts);
}

export function SkeletonText({ lines = 3, className = "" } = {}) {
  const items = Array.from({ length: lines })
    .map((_, i) =>
      block({ h: "0.8rem", w: i === lines - 1 ? "60%" : "100%" })
    )
    .join("");
  return `<div class="skeleton-text ${className}">${items}</div>`;
}

export function SkeletonTable({ rows = 6, cols = 4 } = {}) {
  const head = Array.from({ length: cols })
    .map(() => `<span class="sk-cell sk-th"></span>`)
    .join("");
  const body = Array.from({ length: rows })
    .map(
      () =>
        `<div class="sk-row">${Array.from({ length: cols })
          .map(() => `<span class="sk-cell">${block({ h: "0.8rem", w: "80%" })}</span>`)
          .join("")}</div>`
    )
    .join("");
  return `<div class="skeleton-table"><div class="sk-row sk-head">${head}</div>${body}</div>`;
}

function SkeletonKpi() {
  return `
  <article class="kpi-card" aria-hidden="true">
    <div class="kpi-card__icon kpi-card__icon--ink">${block({ h: "1.125rem", w: "1.125rem", rounded: "0.25rem" })}</div>
    <div class="kpi-card__body">
      ${block({ h: "0.7rem", w: "72%" })}
      ${block({ h: "1.5rem", w: "42%", rounded: "0.4rem" })}
      ${block({ h: "0.7rem", w: "58%" })}
    </div>
  </article>`;
}

function skTitle() {
  return block({ h: "2rem", w: "38%", rounded: "0.5rem" });
}

function skSub() {
  return block({ h: "1rem", w: "60%" });
}

function skPageHead() {
  return `<div class="page-head"><div style="display:flex;flex-direction:column;gap:0.75rem">${skTitle()}${skSub()}</div></div>`;
}

function skPanelHead({ w = "30%" } = {}) {
  return `<div class="panel-head"><div class="panel-title-wrap">${block({ h: "1.25rem", w })}</div></div>`;
}

function skPanel({ headW = "30%", body } = {}) {
  return `<div class="panel">${skPanelHead({ w: headW })}<div class="panel-body" style="display:flex;flex-direction:column;gap:1rem">${body}</div></div>`;
}

function skKpiGrid(n) {
  return `<div class="kpi-grid">${Array.from({ length: n }).map(() => SkeletonKpi()).join("")}</div>`;
}

function skMap({ cls = "" } = {}) {
  return `<div class="map-canvas map-canvas--leaflet skeleton skeleton-map ${cls}"></div>`;
}

function skFoot(w = "140px") {
  return `<div class="map-foot"><span class="skeleton" style="width:${w};height:0.8rem;display:inline-block"></span></div>`;
}

export function SkeletonDashboard() {
  const attention = `
    <div class="attention-row">
      <div class="attention-main">
        ${skPanel({ headW: "40%", body: SkeletonTable({ rows: 4, cols: 4 }) })}
      </div>
      <div class="attention-side">
        ${skPanel({ headW: "40%", body: SkeletonText({ lines: 3 }) })}
        ${skPanel({ headW: "40%", body: SkeletonText({ lines: 3 }) })}
      </div>
    </div>`;

  const sections = `
    <div class="panel" id="case-density-panel">
      ${skPanelHead({ w: "40%" })}
      ${skMap()}
      ${skFoot("120px")}
    </div>
    <div class="cols cols--two">
      ${skPanel({ headW: "40%", body: `<div class="chart">${Array.from({ length: 7 })
        .map(() => `<div class="chart-col"><div class="chart-track"><div class="chart-bar" style="height:60%"></div></div><span class="chart-day"></span></div>`)
        .join("")}</div>` })}
      ${skPanel({ headW: "40%", body: SkeletonText({ lines: 4 }) })}
    </div>
    <div class="cols">
      <div class="col-main">${skPanel({ headW: "40%", body: SkeletonTable({ rows: 5, cols: 4 }) })}</div>
      <div class="col-side">${skPanel({ headW: "40%", body: SkeletonText({ lines: 4 }) })}</div>
    </div>`;

  return [skPageHead(), skKpiGrid(4), attention, sections].join("");
}

export function SkeletonCases() {
  const cards = Array.from({ length: 6 })
    .map(
      () => `
      <article class="case-card">
        <div class="case-card-head">${block({ h: "0.9rem", w: "30%" })}${block({ h: "0.9rem", w: "22%" })}</div>
        <div class="case-card-body" style="display:flex;flex-direction:column;gap:0.5rem;margin-top:0.5rem">
          ${block({ h: "0.8rem", w: "70%" })}${block({ h: "0.8rem", w: "50%" })}
        </div>
        <div class="case-card-foot" style="display:flex;justify-content:space-between;margin-top:0.75rem">
          ${block({ h: "0.8rem", w: "32%" })}${block({ h: "0.8rem", w: "20%" })}
        </div>
      </article>`
    )
    .join("");

  const listCol = `
    <div class="case-list-col">
      <div class="panel case-panel">
        <div class="panel-head">
          <div class="panel-title-wrap"><i data-lucide="clipboard-list"></i><h2 class="panel-title">Cases</h2></div>
        </div>
        <div class="panel-body"><div class="case-grid">${cards}</div></div>
      </div>
    </div>`;

  const mapCol = `
    <div class="case-map-col">
      <div class="panel case-map-panel">
        <div class="panel-head">
          <div class="panel-title-wrap"><i data-lucide="map"></i><h2 class="panel-title">Case map &middot; City of Mati</h2></div>
        </div>
        ${skMap("case-map")}
        ${skFoot("160px")}
      </div>
    </div>`;

  return [
    skPageHead(),
    `<div class="case-kpis">${skKpiGrid(5)}</div>`,
    `<div class="cols case-split">${listCol}${mapCol}</div>`,
  ].join("");
}

export function SkeletonReports() {
  return [
    skPageHead(),
    skKpiGrid(4),
    skPanel({ headW: "30%", body: SkeletonTable({ rows: 7, cols: 5 }) }),
  ].join("");
}

export function SkeletonRescuers() {
  return [
    skPageHead(),
    skKpiGrid(3),
    skPanel({ headW: "30%", body: SkeletonTable({ rows: 6, cols: 5 }) }),
  ].join("");
}

export function SkeletonCaseDetail() {
  const col = (body) => skPanel({ headW: "40%", body });
  return [
    skPageHead(),
    `<div class="case-detail-grid">
      <div class="cd-col-workflow">${col(SkeletonText({ lines: 6 }))}</div>
      <div class="cd-col-info">${col(SkeletonText({ lines: 6 }))}</div>
      <div class="cd-col-files">${col(SkeletonText({ lines: 4 }))}</div>
      <div class="cd-col-rescuer">${col(SkeletonText({ lines: 4 }))}</div>
    </div>`,
  ].join("");
}

export function SkeletonPage({
  title = true,
  kpis = 4,
  panels = 1,
  table = false,
  rows = 6,
  lines = 4,
} = {}) {
  const head = title
    ? `<div class="page-head">
        <div style="display:flex;flex-direction:column;gap:0.75rem">
          ${block({ h: "2rem", w: "40%", rounded: "0.5rem" })}
          ${block({ h: "1rem", w: "60%" })}
        </div>
      </div>`
    : "";

  const kpiGrid = `<div class="kpi-grid">${Array.from({ length: kpis })
    .map(() => SkeletonKpi())
    .join("")}</div>`;

  const panelsHtml = Array.from({ length: panels })
    .map(() =>
      skPanel({ body: table ? SkeletonTable({ rows }) : SkeletonText({ lines }) })
    )
    .join("");

  return [head, kpiGrid, panelsHtml].join("");
}
