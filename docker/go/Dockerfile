FROM golang:alpine AS builder

WORKDIR /build

RUN apk add --no-cache git

COPY sms-gateway/go.mod sms-gateway/go.sum ./
RUN go mod download

COPY sms-gateway/ ./

RUN go mod tidy
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o server ./cmd/server

FROM alpine:latest

RUN apk --no-cache add ca-certificates tzdata

WORKDIR /app

COPY --from=builder /build/server .
COPY --from=builder /build/configs ./configs/

RUN chmod +x server

EXPOSE 8080

ENV GIN_MODE=release
ENV TZ=Asia/Shanghai

CMD ["./server"]
