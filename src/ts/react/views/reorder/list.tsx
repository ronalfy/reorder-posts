import React, { useState } from "react";
import {
	Tree,
	getBackendOptions,
	MultiBackend,
	type NodeModel,
} from "@minoru/react-dnd-treeview";
import { DndProvider } from "react-dnd";
import { reorderClasses } from "./classes";
import { PostData } from "./types";
import { CustomNode } from "./CustomNode";
import { Placeholder } from "./Placeholder";
import { CustomDragPreview } from "./CustomDragPreview";

const postsToTreeNodes = (
	posts: PostData[],
	hierarchical: boolean
): NodeModel< PostData >[] => {
	const childCounts = posts.reduce< Record< number, number > >( ( counts, post ) => {
		const parentId = post.parent ?? 0;
		counts[ parentId ] = ( counts[ parentId ] || 0 ) + 1;
		return counts;
	}, {} );

	return posts.map( ( post ) => ( {
		id: post.id,
		parent: post.parent ?? 0,
		text: post.title,
		data: post,
		droppable: hierarchical || ( childCounts[ post.id ] ?? 0 ) > 0,
	} ) );
};

const List = ( {
	data,
	hierarchical = false,
}: {
	data: PostData[];
	hierarchical?: boolean;
} ) => {
	const [ treeData, setTreeData ] = useState< NodeModel< PostData >[] >( () =>
		postsToTreeNodes( data, hierarchical )
	);

	const handleDrop = ( newTree: NodeModel< PostData >[] ) => {
		setTreeData( newTree );
	};

	return (
		<div className="reorder-posts-list">
			<DndProvider backend={ MultiBackend } options={ getBackendOptions() }>
				<Tree< PostData >
					tree={ treeData }
					rootId={ 0 }
					sort={ false }
					onDrop={ handleDrop }
					render={ ( node, { depth, isOpen, onToggle, isDropTarget, isDragging } ) => (
						<CustomNode
							node={ node }
							depth={ depth }
							isOpen={ isOpen }
							isDropTarget={ isDropTarget }
							isDragging={ isDragging }
							onToggle={ onToggle }
						/>
					) }
					dragPreviewRender={ ( monitorProps ) => (
						<CustomDragPreview monitorProps={ monitorProps } />
					) }
					classes={ {
						root: reorderClasses.treeRoot,
						draggingSource: reorderClasses.draggingSource,
						placeholder: reorderClasses.placeholderContainer,
						dropTarget: reorderClasses.dropTarget,
					} }
					canDrop={ ( tree, { dragSource, dropTargetId } ) => {
						if ( dragSource?.parent === dropTargetId ) {
							return true;
						}
					} }
					dropTargetOffset={ 10 }
					placeholderRender={ ( node, { depth } ) => (
						<Placeholder node={ node } depth={ depth } />
					) }
				/>
			</DndProvider>
		</div>
	);
};

export default List;
