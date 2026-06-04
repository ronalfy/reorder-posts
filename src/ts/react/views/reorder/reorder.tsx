import React, { useEffect, useState } from "react";
import apiFetch from '@wordpress/api-fetch';

type Post = {
  id: number;
  title: string;
  url: string;
  menu_order: number;
};

type Response = {
  posts: Post[];
};

type Props = {
  postsPerPage: number;
  postType: string;
  nonce: string;
  hierarchical: boolean;
  postStatus: string[];
};

const Reorder = ({ postsPerPage, postType, nonce, hierarchical, postStatus }: Props) => {
  const [posts, setPosts] = useState<Post[]>([]);

  useEffect(() => {
	const getPosts = async () => {
	  const response = await apiFetch<Response>({ path: `/reorder-posts/v1/posts?post_type=${postType}&posts_per_page=${postsPerPage}&offset=0&order=ASC&nonce=${nonce}&hierarchical=${hierarchical}&post_status=${postStatus.join(",")}`, method: "GET" }).then((response) => {
		return response as Response;
	  }).catch((error) => {
		console.error(error);
		return { posts: [] } as Response;
	  });
	  setPosts(response.posts || []);
	};
	getPosts();
  }, []);
  return (
    <div>
      <h1>Reorder</h1>
    </div>
  );
};

export default Reorder;