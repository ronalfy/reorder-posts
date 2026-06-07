import React from "react";
import { DragLayerMonitorProps } from "@minoru/react-dnd-treeview";
import { reorderClasses } from "./classes";
import { PostData } from "./types";
import { TypeIcon } from "./TypeIcon";

type Props = {
	monitorProps: DragLayerMonitorProps<PostData>;
};

export const CustomDragPreview: React.FC<Props> = (props) => {
	const item = props.monitorProps.item;

	return (
		<div className={reorderClasses.dragPreview}>
			<div className={reorderClasses.dragPreviewIcon}>
				<TypeIcon droppable={item.droppable || false} />
			</div>
			<div className={reorderClasses.dragPreviewLabel}>{item.text}</div>
		</div>
	);
};
