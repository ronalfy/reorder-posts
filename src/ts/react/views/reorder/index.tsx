import React from "react";
import { createRoot } from "react-dom/client";
import Reorder from "./reorder";

/* Set types for container. */
type Container = HTMLElement | null;

const container: Container = document.getElementById("reorder-posts-interface");
if (container) {
	const postsPerPage = container.getAttribute("data-posts-per-page");
	const postType = container.getAttribute("data-post-type");
	const nonce = container.getAttribute("data-nonce");
	const hierarchical = container.getAttribute("data-hierarchical");
	const postStatus = container.getAttribute("data-post-status");

	const root = createRoot(container);
	root.render(
		<Reorder
			postsPerPage={parseInt(postsPerPage || "50")}
			postType={postType || ""}
			nonce={nonce || ""}
			hierarchical={hierarchical === "true"}
			postStatus={postStatus?.split(",") || []}
		/>
	);
}
