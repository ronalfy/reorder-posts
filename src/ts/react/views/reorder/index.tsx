import { createRoot } from "react-dom";
import React from "react";
const ReorderPostsInterface = () => {
  return (
    <div>
      <h1>Reorder Posts React</h1>
    </div>
  );
};

/* Set types for container. */
type Container = HTMLElement | null;

const container: Container = document.getElementById(
	"reorder-posts-interface"
);
if (container) {
	const root = createRoot(container);
	root.render(<ReorderPostsInterface />);
}
