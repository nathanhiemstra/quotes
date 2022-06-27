import React from "react"
import { graphql } from "gatsby"
import AuthorBio from "../components/AuthorBio"

const WpPost = ({ data }) => {
  const {
    wpPost: { title, content, id, author },
  } = data
  return (
    <div className={`post-${id}`}>
      <h1>{title}</h1>
      <div dangerouslySetInnerHTML={{ __html: content }} />
      <AuthorBio author={author.node} />
    </div>
  )
}

export const query = graphql`
  query($id: String) {
    wpPost(id: { eq: $id }) {
      id
      title
      content
      author {
        node {
          ...AuthorBio
        }
      }
    }
  }
`

export default WpPost
